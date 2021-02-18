<?php
declare(strict_types = 1);

namespace App\Schedule;

use App\Component\Serializer\JsonSerializer;
use App\Component\Serializer\ObjectSerializer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nsq\Nsq;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\ApplicationContext;
use App\Component\Serializer\ClosureSerializer;

abstract class AbstractQueue implements QueueInterface
{
    /**
     * @var string
     */
    protected $topic = 'task-schedule-';

    /**
     * @var string
     */
    protected $channel = 'default';

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
     * @var ObjectSerializer
     */
    protected $phpSerializer;

    /**
     * @var ClosureSerializer
     */
    protected $closureSerializer;

    /**
     * @var
     */
    protected $jsonSerializer;

    /**
     * @var string
     */
    protected $redisPool;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(string $channel, string $redisPool = 'default')
    {
        $this->channel           = $channel;
        $this->redisPool         = $redisPool;
        $this->phpSerializer     = make(ObjectSerializer::class);
        $this->closureSerializer = make(ClosureSerializer::class);
        $this->jsonSerializer    = make(JsonSerializer::class);
        $this->logger            = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
    }

    /**
     * @return \Hyperf\Redis\RedisProxy
     */
    protected function redis() : RedisProxy
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($this->redisPool);
    }

    /**
     * @return \Hyperf\Nsq\Nsq
     */
    protected function nsq() : Nsq
    {
        return ApplicationContext::getContainer()->get(Nsq::class);
    }

    /**
     * Get name of the channel.
     *
     * @return string
     */
    public function getChannel() : string
    {
        return $this->channel;
    }

    /**
     * Moved the expired job to waiting queue.
     */
    abstract public function migrateExpired() : void;

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isWaiting(int $id) : bool
    {
        return self::STATUS_WAITING === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isReserved(int $id) : bool
    {
        return self::STATUS_RESERVED === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isDone(int $id) : bool
    {
        return self::STATUS_DONE === $this->getStatus($id);
    }

    /**
     * @param int $id of a job message
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function isFailed(int $id) : bool
    {
        return self::STATUS_FAILED === $this->getStatus($id);
    }
}
