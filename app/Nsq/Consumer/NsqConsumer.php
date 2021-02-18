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
use InvalidArgumentException;
use Hyperf\Utils\Pipeline;

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

    /**
     * @var Pipeline
     */
    protected $pipeline;

    public function consume(Message $message) : ?string
    {

        $this->jsonSerializer   = $this->container->get(JsonSerializer::class);
        $this->objectSerializer = $this->container->get(ObjectSerializer::class);
        $this->pipeline         = $this->container->get(Pipeline::class);
        $body                   = $this->jsonSerializer->denormalize($message->getBody());
        if ($job = $this->objectSerializer->denormalize($body['serializedMessage'])) {
            $this->handle($job);
        }

        return Result::ACK;
    }

    /**
     * @param JobInterface|\Closure $handler
     */
    protected function handle($handler) : void
    {
        try {
            if (empty($handler)) {
                throw new InvalidArgumentException('Job is empty.');
            }
            is_callable($handler) ? $handler() : $this->pipeline->send($handler)
                                                                ->through($handler->middleware())
                                                                ->then(function (JobInterface $job)
                                                                {
                                                                    $job->handle();
                                                                });
        } catch (\Throwable $throwable) {

        }
    }
}
