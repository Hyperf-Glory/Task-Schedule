<?php
declare(strict_types = 1);
namespace App\Kernel\Nsq;

use App\Constants\Serializer;
use App\Schedule\AbstractQueue;
use App\Schedule\JobInterface;
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

        if ($redis->hexists("{$this->topic}{$this->channel}:messages", $id)) {
            $status = self::STATUS_WAITING;
        }

        if ($redis->zscore("{$this->topic}{$this->channel}:reserved", $id)) {
            $status = self::STATUS_RESERVED;
        }

        if ($redis->hexists("{$this->topic}{$this->channel}:failed", $id)) {
            $status = self::STATUS_FAILED;
        }

        return $status;
    }

    /**
     * @param \App\Schedule\JobInterface|\Closure $message
     * @param float                               $defer
     *
     */
    public function push($message, float $defer = 0) : void
    {
        $serializedMessage = null;
        $serializerType    = null;

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
        $id    = $redis->incr("{$this->topic}{$this->channel}:message_id");
        $redis->hset("{$this->topic}{$this->channel}:messages", (string)$id, $pushMessage);

        if ($defer > 0) {
            $redis->zadd("{$this->topic}{$this->channel}:delayed", $id, time() + $defer);
        } else {
            $redis->lpush("{$this->topic}{$this->channel}:waiting", $id);
        }

        //push nsqd
        $nsq = $this->nsq();
        try {
            if (!$nsq->publish($this->topic . $this->channel, $this->jsonSerializer->normalize([
                'id'                => $id,
                'serializerType'    => $serializerType,
                'serializedMessage' => $serializedMessage,
            ]), $defer)) {
                $this->logger->debug(sprintf('Debug when job push: [%s] fail.', $serializerType));
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error when job push: [%s] fail.Message: [%s]', $serializerType, $e->getMessage()));
        }
    }

    public function remove(int $id) : void
    {
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
}

