<?php

declare(strict_types = 1);

namespace App\Nsq\Consumer;

use App\Schedule\JobInterface;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;
use App\Component\Serializer\JsonSerializer;
use App\Component\Serializer\ObjectSerializer;

/**
 * @Consumer(topic="task-schedule-queue", channel="task-schedule", name ="NsqConsumer", nums=1)
 */
class NsqConsumer extends AbstractConsumer
{
    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var ObjectSerializer
     */
    protected $objectSerializer;

    public function consume(Message $message) : ?string
    {

        $this->jsonSerializer   = $this->container->get(JsonSerializer::class);
        $this->objectSerializer = $this->container->get(ObjectSerializer::class);
        $body                   = $this->jsonSerializer->denormalize($message->getBody());
        if ($job = $this->objectSerializer->denormalize($body['serializedMessage'])) {

        }

        return Result::ACK;
    }

    protected function handle($handler) : void
    {
        try {

        } catch (\Throwable $throwable) {

        }
    }
}
