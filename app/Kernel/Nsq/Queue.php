<?php
declare(strict_types = 1);
namespace App\Kernel\Nsq;

use App\Constants\Serializer;
use App\Kernel\Redis\LuaScript;
use App\Schedule\AbstractQueue;
use App\Schedule\JobInterface;
use Hyperf\Engine\Channel;
use Hyperf\Engine\Exception\RuntimeException;
use Hyperf\Utils\Coroutine;
use InvalidArgumentException;
use Throwable;

class Queue extends AbstractQueue
{

    /**
     * @inheritdoc
     */
    public function migrateExpired() : void
    {
        $redis = $this->redis();
        $redis->eval(LuaScript::migrateExpiredJobs(), [
            $this->redisKey() . ":delayed",
            $this->redisKey() . ":waiting",
            time()
        ], 2);
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     *
     * @return int
     */
    public function getStatus(int $id) : int
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid message ID: $id.");
        }

        $redis = $this->redis();

        $status = self::STATUS_DONE;

        if ($redis->hexists($this->redisKey() . ":messages", $id)) {
            $status = self::STATUS_WAITING;
        }

        if ($redis->zscore($this->redisKey() . ":reserved", $id)) {
            $status = self::STATUS_RESERVED;
        }

        if ($redis->hexists($this->redisKey() . ":failed", $id)) {
            $status = self::STATUS_FAILED;
        }

        return $status;
    }

    /**
     * @inheritdoc
     *
     * @param \App\Schedule\JobInterface|\Closure $message
     * @param float                               $defer
     */
    public function push($message, float $defer = 0) : void
    {
        $queue = new Channel(1);
        Coroutine::create(function () use ($queue, $message, &$serializerType, &$serializedMessage, $defer)
        {
            try {
                if (is_callable($message)) {
                    $serializedMessage = $this->closureSerializer->normalize($message);
                    $serializerType    = Serializer::SERIALIZER_TYPE_CLOSURE;
                } elseif ($message instanceof JobInterface) {
                    $serializedMessage = $this->phpSerializer->normalize($message);
                    $serializerType    = Serializer::SERIALIZER_TYPE_PHP;
                } else {
                    $type = is_object($message) ? get_class($message) : gettype($message);
                    throw new InvalidArgumentException($type . ' type message is not allowed.');
                }

                $pushMessage = $this->jsonSerializer->normalize([
                    'serializerType'    => $serializerType,
                    'serializedMessage' => $serializedMessage
                ]);

                //Use Redis to store records and statistics
                $redis = $this->redis();
                $id    = $redis->incr($this->redisKey() . ":message_id");
                //Redis exec
                $redis->multi();
                $redis->hset($this->redisKey() . ":messages", (string)$id, $pushMessage);
                if ($defer > 0) {
                    $redis->zadd($this->redisKey() . ":delayed", $id, time() + $defer);
                }
                $ret = $redis->exec();
                if (!$ret[0]) {
                    //channel push failed.
                    throw new RuntimeException(sprintf('Redis Multi Exec Action [%s] Failed.', 'hset'));
                }
                $queue->push($id);
            } catch (Throwable $throwable) {
                $this->logger->error(sprintf('Error in Redis operation or channel push [%s]', $throwable->getMessage()));
                $queue->close();
            }
        });

        //push nsq
        Coroutine::create(function () use ($queue, $defer)
        {
            try {
                if ($queue->isClosing()) {
                    throw new RuntimeException('Channel is Close.');
                }
                $id = $queue->pop();
                $queue->close();
                $nsq = $this->nsq();
                if (!$nsq->publish($this->topic, $this->jsonSerializer->normalize([
                    'id' => $id,
                ]), $defer)) {
                    $this->logger->warning('Warning when job nsq push fail.');
                }
            } catch (Throwable $e) {
                $this->logger->error(sprintf('Error when job push fail.Message: [%s].', $e->getMessage()));
            }
        });
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     */
    public function remove(int $id) : void
    {
        wait(function () use ($id)
        {
            $redis = $this->redis();
            $redis->eval(
                LuaScript::remove(),
                [
                    $this->redisKey() . ":reserved",
                    $this->redisKey() . ":attempts",
                    $this->redisKey() . ":failed",
                    $this->redisKey() . ":messages",
                    $id
                ],
                4,
            );
        });
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     * @param int $delay
     */
    public function release(int $id, int $delay = 0) : void
    {
        wait(function () use ($id, $delay)
        {
            $redis = $this->redis();
            $redis->eval(
                LuaScript::release(),
                [
                    $this->redisKey() . ":delayed",
                    $this->redisKey() . ":reserved",
                    $id,
                    time() + $delay
                ],
                2
            );
        }, $this->waiterTimeout);
    }

    /**
     * @inheritdoc
     *
     * @param int         $id
     * @param null|string $payload
     */
    public function failed(int $id, string $payload = null) : void
    {
        wait(function () use ($id, $payload)
        {
            $redis = $this->redis();
            $redis->eval(
                LuaScript::fail(),
                [
                    $this->redisKey() . ":failed",
                    $this->redisKey() . ":reserved",
                    $id,
                    $payload
                ],
                2
            );
        }, $this->waiterTimeout);
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function getFailed() : array
    {
        return wait(function ()
        {
            $redis = $this->redis();

            $failedJobs = [];
            $cursor     = 0;
            do {
                [$cursor, $data] = $redis->hscan($this->redisKey() . ":failed", $cursor, [
                    'COUNT' => 10,
                ]);
                $failedJobs += $data;
            } while ($cursor !== 0);

            return $failedJobs;
        });
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     */
    public function clearFailed(int $id) : void
    {
        wait(function () use ($id)
        {
            $redis = $this->redis();

            $redis->hdel($this->redisKey() . ":failed", (string)$id);
        });
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     * @param int $delay
     */
    public function reloadFailed(int $id, int $delay = 0) : void
    {
        wait(function () use ($id, $delay)
        {
            $redis = $this->redis();
            $redis->eval(
                LuaScript::reloadFail(),
                [
                    $this->redisKey() . ":delayed",
                    $this->redisKey() . ":failed",
                    $id,
                    time() + $delay
                ], 2
            );
        }, $this->waiterTimeout);
    }

    /**
     * @inheritdoc
     */
    public function clear() : void
    {
        $redis = $this->redis();

        // delete reserved queue
        while ($redis->zcard($this->redisKey() . ":reserved") > 0) {
            $redis->zremrangebyrank($this->redisKey() . ":reserved", 0, 499);
        }

        // delete delayed queue
        while ($redis->zcard($this->redisKey() . ":delayed") > 0) {
            $redis->zremrangebyrank($this->redisKey() . ":delayed", 0, 499);
        }

        // delete failed queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->redisKey() . ":failed", $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->redisKey() . ":failed", implode(',', $fields));
            }
        } while ($cursor !== 0);

        // delete attempts queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->redisKey() . ":attempts", $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->redisKey() . ":attempts", implode(',', $fields));
            }
        } while ($cursor !== 0);

        // delete messages queue
        $cursor = 0;
        do {
            [$cursor, $data] = $redis->hscan($this->redisKey() . ":messages", $cursor, ['COUNT' => 200]);
            if (!empty($fields = array_keys($data))) {
                $redis->hdel($this->redisKey() . ":messages", implode(',', $fields));
            }
        } while ($cursor !== 0);

        $iterator = null;
        while (true) {
            $keys = $redis->scan($iterator, $this->redisKey() . ':*', 50);
            if ($keys === false) {
                return;
            }
            $redis->del($keys);
        }
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function status() : array
    {
        return wait(function ()
        {
            $redis = $this->redis();

            $pipe = $redis->pipeline();
            $pipe->get($this->redisKey() . ":message_id");
            $pipe->zcard($this->redisKey() . ":reserved");
            $pipe->llen($this->redisKey() . ":waiting");
            $pipe->zcount($this->redisKey() . ":delayed", '-inf', '+inf');
            $pipe->hlen($this->redisKey() . ":failed");
            [$total, $reserved, $waiting, $delayed, $failed] = $pipe->exec();

            $done = ($total ?? 0) - $waiting - $delayed - $reserved - $failed;

            return [$waiting, $reserved, $delayed, $done, $failed, $total ?? 0];
        }, $this->waiterTimeout);
    }

    /**
     * @inheritdoc
     */
    public function retryReserved() : void
    {
        wait(function ()
        {
            $redis = $this->redis();
            $ids   = $redis->zrange($this->redisKey() . ":reserved", 0, -1);
            foreach ($ids as $id) {
                $this->release((int)$id);
            }
        }, $this->waiterTimeout);
    }

    /**
     * Gets the redis key name
     * @return string
     */
    protected function redisKey() : string
    {
        return sprintf('%s-%s', $this->topic, $this->channel);
    }

    public function attemptsIncr(int $id) : void
    {
        wait(function () use ($id)
        {
            $this->redis()->hIncrBy($this->redisKey() . ":attempts", (string)$id, 1);
        }, $this->waiterTimeout);
    }

    /**
     * @inheritdoc
     *
     * @param int $id
     *
     * @return array
     */
    public function get(int $id) : array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("Invalid message ID: $id.");
        }
        $chan = new Channel(1);
        Coroutine::create(function () use ($chan, $id)
        {
            $redis = $this->redis();

            $attempts = $redis->hget($this->redisKey() . ":attempts", (string)$id);
            $payload  = $redis->hget($this->redisKey() . ":messages", (string)$id);

            try {
                if (empty($payload) || empty($message = $this->jsonSerializer->denormalize($payload)) || !isset($message['serializerType'])) {
                    throw new InvalidArgumentException(sprintf('Broken message payload[%d]: %s', $id, $payload));
                }
                $chan->push([
                    $id,
                    $attempts,
                    $message['serializedMessage']
                ]);
            } catch (Throwable $throwable) {
                $this->logger->error($throwable->getMessage());
                $chan->push(false);
            }
        });

        if ($chan->isClosing() || !($data = $chan->pop())) {
            throw new RuntimeException('Channel push false,Task ID:' . $id);
        }
        return $data;
    }
}

