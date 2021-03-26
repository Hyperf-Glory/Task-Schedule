<?php

declare(strict_types = 1);

namespace App\Nsq\Consumer;

use App\Schedule\JobInterface;
use Carbon\Carbon;
use Codedungeon\PHPCliColors\Color;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Nsq;
use Hyperf\Nsq\Result;
use App\Component\Serializer\JsonSerializer;
use App\Component\Serializer\ObjectSerializer;
use Hyperf\Utils\Coroutine;
use InvalidArgumentException;
use Hyperf\Utils\Pipeline;
use Psr\Container\ContainerInterface;
use App\Kernel\Nsq\Queue;
use ReflectionClass;
use Swoole\Timer;
use Throwable;

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

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

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

    /**
     * @var int
     */
    protected $timerId;

    /**
     * @var int
     */
    protected $interval = 10000;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->queue = make(Queue::class, [
            'channel' => $this->channel
        ]);
        $this->setTopic($this->queue->getTopic());
        $this->setChannel($this->queue->getChannel());
        $this->setName($this->getShortCLassName());
        $this->setNums(2);
        //set nsq pool
        $this->setPool('default');
        $this->timerId          = Timer::tick($this->interval, function ()
        {
            $this->tick();
        });
        $this->jsonSerializer   = $this->container->get(JsonSerializer::class);
        $this->objectSerializer = $this->container->get(ObjectSerializer::class);
        $this->pipeline         = $this->container->get(Pipeline::class);
        $this->logger           = $this->container->get(StdoutLoggerInterface::class);
        $this->logger->info(sprintf('TimerTickID#%s started.', $this->timerId));
    }

    public function consume(Message $message) : ?string
    {
        ['id' => $id] = $this->jsonSerializer->denormalize($message->getBody());

        if (!$id) {
            $this->logger->error('Invalid task ID:' . $id);
            return Result::DROP;
        }
        try {
            $this->handle((int)$id);
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                'Uncaptured exception[%s:%s] detected in %s::%d.',
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ), [
                'driver'  => get_class($this->queue),
                'channel' => $this->queue->getChannel(),
                'id'      => $id,
            ]);
            try {
                if ($this->queue->isReserved($id)) {
                    $this->queue->release($id, 60);
                }
            } catch (Throwable $e) {
                $this->logger->error($e->getMessage());
            }
            return Result::DROP;
        }

        return Result::ACK;
    }

    /**
     * The processing logic for the current task
     *
     * @param int $id
     *
     * @throws \Throwable
     */
    protected function handle(int $id) : void
    {
        try {
            /** @var $job \Closure|JobInterface */
            [, $attempts, $job] = $this->queue->get($id);
            $job = $this->objectSerializer->denormalize($job);
            if (empty($job)) {
                throw new InvalidArgumentException('Job popped is empty.');
            }
            $this->queue->attemptsIncr($id);
            echo Color::GREEN, sprintf('Task ID:[%s] Time:[%s] start execution#', $id, Carbon::now()->toDateTimeString()), ' ', Color::CYAN, PHP_EOL;
            is_callable($job) ? $job() : $this->pipeline->send($job)
                                                        ->through($job->middleware())
                                                        ->then(function (JobInterface $job)
                                                        {
                                                            $job->handle();
                                                        });
            echo Color::YELLOW, sprintf('Task ID:[%s] Time:[%s] completed#', $id, Carbon::now()->toDateTimeString()), ' ', Color::CYAN, PHP_EOL;
            $this->queue->remove($id);
        } catch (Throwable $throwable) {
            $attempts = (int)($attempts ?? 0);
            $payload  = [
                'last_error'         => get_class($throwable),
                'last_error_message' => $throwable->getMessage(),
                'attempts'           => $attempts,
            ];
            if (!isset($job) || !$job instanceof JobInterface) {
                $this->queue->failed($id, json_encode($payload, JSON_THROW_ON_ERROR));
            } elseif ($job->canRetry($attempts, $throwable)) {
                $delay = max($job->retryAfter($attempts), 0);
                $this->queue->release($id, $delay);
                Coroutine::create(function () use ($id, $delay)
                {
                    $this->container->get(Nsq::class)->publish($this->topic, $this->jsonSerializer->normalize([
                        'id' => $id,
                    ]), $delay + random_int(0, 10));
                });
            } else {
                $this->queue->failed($id, json_encode($payload, JSON_THROW_ON_ERROR));
                $job->failed($id, $payload);
            }
            $this->logger->error(sprintf(
                'Error when job executed: [%s]:[%s] detected in %s::%d.',
                get_class($throwable),
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine()
            ), [
                'driver'   => get_class($this->queue),
                'channel'  => $this->queue->getChannel(),
                'id'       => $id,
                'attempts' => $attempts,
            ]);
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

    protected function tick() : void
    {
        $this->logger->info(sprintf('TimerTick#[%s] Execute Time:%s', $this->timerId, Carbon::now()->toDateTimeString()));
        $this->queue->migrateExpired();
    }
}
