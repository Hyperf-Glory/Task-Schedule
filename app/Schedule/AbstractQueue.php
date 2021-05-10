<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Schedule;

use App\Component\Serializer\ClosureSerializer;
use App\Component\Serializer\JsonSerializer;
use App\Component\Serializer\ObjectSerializer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nsq\Nsq;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\ApplicationContext;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractQueue implements QueueInterface
{
    /**
     * @see AbstractQueue::isWaiting()
     */
    public const STATUS_WAITING = 1;

    /**
     * @see AbstractQueue::isReserved()
     */
    public const STATUS_RESERVED = 2;

    /**
     * @see AbstractQueue::isDone()
     */
    public const STATUS_DONE = 3;

    /**
     * @see AbstractQueue::isFailed()
     */
    public const STATUS_FAILED = 4;

    /**
     * @var float
     */
    protected $waiterTimeout = 5.0;

    /**
     * @var string
     */
    protected $topic = 'task-schedule';

    /*`
     * @var string
     */
    protected $channel = 'queue';

    /**
     * @var ObjectSerializer
     */
    protected $phpSerializer;

    /**
     * @var ClosureSerializer
     */
    protected $closureSerializer;

    /**
     * @var JsonSerializer
     */
    protected $jsonSerializer;

    /**
     * @var string
     */
    protected $redisPool;

    /**
     * @var mixed|\Psr\EventDispatcher\EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(string $channel, string $redisPool = 'default')
    {
        $this->channel = $channel;
        $this->redisPool = $redisPool;
        $this->phpSerializer = make(ObjectSerializer::class);
        $this->closureSerializer = make(ClosureSerializer::class);
        $this->jsonSerializer = make(JsonSerializer::class);
        $this->logger = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
        $this->eventDispatcher = ApplicationContext::getContainer()->get(EventDispatcherInterface::class);
    }

    /**
     * Get name of the channel.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Moved the expired job to waiting queue.
     */
    abstract public function migrateExpired(): void;

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isWaiting(int $id): bool
    {
        return $this->getStatus($id) === self::STATUS_WAITING;
    }

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isReserved(int $id): bool
    {
        return $this->getStatus($id) === self::STATUS_RESERVED;
    }

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isDone(int $id): bool
    {
        return $this->getStatus($id) === self::STATUS_DONE;
    }

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isFailed(int $id): bool
    {
        return $this->getStatus($id) === self::STATUS_FAILED;
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    protected function redis(): RedisProxy
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($this->redisPool);
    }

    protected function nsq(): Nsq
    {
        //or make(Nsq::class,[......])
        return ApplicationContext::getContainer()->get(Nsq::class);
    }
}
