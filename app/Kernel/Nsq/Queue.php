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

class Queue extends AbstractQueue
{

    public function migrateExpired() : void
    {
    }

    /**
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
     * @param \App\Schedule\JobInterface|\Closure $message
     * @param float                               $defer
     */
    public function push($message, float $defer = 0) : void
    {
        $serializedMessage = null;
        $serializerType    = null;
        $queue             = new Channel(1);
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
                $queue->push($id);
                //Redis exec
                $redis->multi();
                $redis->hset($this->redisKey() . ":messages", (string)$id, $pushMessage);

                $redis->hIncrBy($this->redisKey() . ":attempts", (string)$id, 1);
                if ($defer > 0) {
                    $redis->zadd($this->redisKey() . ":delayed", $id, time() + $defer);
                } else {
                    $redis->lpush($this->redisKey() . ":waiting", $id);
                }
                $redis->exec();
            } catch (\Throwable $throwable) {
                $this->logger->error(sprintf('Error in Redis operation or channel push [%s]', $throwable->getMessage()));
            }
        });

        //push nsq
        Coroutine::create(function () use ($queue, $serializerType, $serializedMessage, $defer)
        {
            try {
                if ($queue->isClosing()) {
                    throw new RuntimeException('Channel is Close.');
                }
                $id = $queue->pop();
                $queue->close();
                $nsq = $this->nsq();
                if (!$nsq->publish($this->topic, $this->jsonSerializer->normalize([
                    'id'                => $id,
                    'serializerType'    => $serializerType,
                    'serializedMessage' => $serializedMessage,
                ]), $defer)) {
                    $this->logger->warning('Warning when job nsq push fail.');
                }
            } catch (\Throwable $e) {
                $this->logger->error(sprintf('Error when job push fail.Message: [%s].', $e->getMessage()));
            }
        });
    }

    /**
     * @param int $id
     */
    public function remove(int $id) : void
    {
        Coroutine::create(function () use ($id)
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

    public function release(int $id, int $delay = 0) : void
    {
    }

    public function failed(int $id, string $payload = null) : void
    {
    }

    public function getFailed() : array
    {
    }

    public function clearFailed(int $id) : void
    {
    }

    public function reloadFailed(int $id, int $delay = 0) : void
    {
    }

    public function clear() : void
    {
    }

    public function status() : array
    {
    }

    public function retryReserved() : void
    {
    }

    /**
     * Gets the redis key name
     * @return string
     */
    protected function redisKey() : string
    {
        return sprintf('%s-%s', $this->topic, $this->channel);
    }

}

