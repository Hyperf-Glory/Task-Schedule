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

        if ($redis->hexists("{$this->channelPrefix}{$this->channel}:messages", $id)) {
            $status = self::STATUS_WAITING;
        }

        if ($redis->zscore("{$this->channelPrefix}{$this->channel}:reserved", $id)) {
            $status = self::STATUS_RESERVED;
        }

        if ($redis->hexists("{$this->channelPrefix}{$this->channel}:failed", $id)) {
            $status = self::STATUS_FAILED;
        }

        return $status;
    }

    /**
     * @param \App\Schedule\JobInterface|\Closure $message
     * @param int                                 $delay
     *
     * @throws \JsonException
     */
    public function push($message, int $delay = 0) : void
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

        $pushMessage = json_encode([
            'serializerType'    => $serializerType,
            'serializedMessage' => $serializedMessage,
        ], JSON_THROW_ON_ERROR);

        //Use Redis to store records and statistics
        $redis = $this->redis();
        $id    = $redis->incr("{$this->channelPrefix}{$this->channel}:message_id");
        $redis->hset("{$this->channelPrefix}{$this->channel}:messages", $id, $pushMessage);

        if ($delay > 0) {
            $redis->zadd("{$this->channelPrefix}{$this->channel}:delayed", $id, time() + $delay);
        } else {
            $redis->lpush("{$this->channelPrefix}{$this->channel}:waiting", $id);
        }

        //push nsqd
        $nsq = $this->nsq();
        try {
            if (!$nsq->publish($this->channelPrefix . $this->channel, json_encode([
                'id'                => $id,
                'serializerType'    => $serializerType,
                'serializedMessage' => $serializedMessage,
            ], JSON_THROW_ON_ERROR))) {
                $this->logger->debug(sprintf('Debug when job push: [%s] [%s] fail.', $serializerType, $serializedMessage));
            }
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Error when job push: [%s] [%s] fail.', $serializerType, $serializedMessage));
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

