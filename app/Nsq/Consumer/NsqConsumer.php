<?php

declare(strict_types = 1);

namespace App\Nsq\Consumer;

use App\Constants\Serializer;
use App\Schedule\JobInterface;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;
use App\Component\Serializer\JsonSerializer;
use App\Component\Serializer\ObjectSerializer;
use InvalidArgumentException;
use Hyperf\Utils\Pipeline;
use Psr\Container\ContainerInterface;
use App\Kernel\Nsq\Queue;
use ReflectionClass;

/**
 * @Consumer()
 */
class NsqConsumer extends AbstractConsumer
{
    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var string
     */
    protected $channel = 'queue';

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->queue = make(Queue::class, [
            'channel' => $this->channel
        ]);
        $this->setTopic($this->queue->getTopic());
        $this->setChannel($this->queue->getChannel());
        $this->setName($this->getShortCLassName());
        $this->setNums(1);
        $this->jsonSerializer   = $this->container->get(JsonSerializer::class);
        $this->objectSerializer = $this->container->get(ObjectSerializer::class);
        $this->pipeline         = $this->container->get(Pipeline::class);
    }

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

        ['id' => $id, 'serializerType' => $type, 'serializedMessage' => $body] = $this->jsonSerializer->denormalize($message->getBody());
        if ((int)$id >= 0 && ($job = $this->objectSerializer->denormalize($body)) && in_array($type, [
                Serializer::SERIALIZER_TYPE_CLOSURE,
                Serializer::SERIALIZER_TYPE_PHP
            ], true)) {
            $this->handle((int)$id, $job);
        }

        return Result::ACK;
    }

    /**
     * The processing logic for the current task
     *
     * @param int                   $id
     * @param JobInterface|\Closure $handler
     */
    protected function handle(int $id, $handler) : void
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
            $this->queue->remove($id);
        } catch (\Throwable $throwable) {

        }
    }

    /**
     * Gets the current class name
     *
     * @return string
     */
    protected function getShortCLassName() : string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
