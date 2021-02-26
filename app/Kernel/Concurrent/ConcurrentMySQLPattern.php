<?php
declare(strict_types = 1);

namespace App\Kernel\Concurrent;

use Hyperf\Engine\Channel;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Psr\Log\LoggerInterface;
use App\Schedule\JobInterface;
use Throwable;

class ConcurrentMySQLPattern
{
    /**
     * @var ?\PDO
     */
    protected $PDO;

    /**
     * @var ?Channel
     */
    protected $chan;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(\PDO $PDO, LoggerInterface $logger = null)
    {
        $this->PDO    = $PDO;
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    public function isTransaction() : bool
    {
        return $this->PDO->inTransaction();
    }

    public function isOpen() : bool
    {
        return $this->PDO !== null;
    }

    public function loop() : void
    {
        $this->chan = new Channel(1);
        Coroutine::create(function ()
        {
            while (true) {
                try {
                    $closure = $this->chan->pop();
                    if (!$closure) {
                        break;
                    }
                    $closure->call($this);
                } catch (Throwable $e) {
                    $this->logger->error('Pdo error:' . $e->getMessage());
                    $this->PDO = null;
                    break;
                }
            }
        });

        static $once;
        if (!isset($once)) {
            $once = true;
            Coroutine::create(function ()
            {
                CoordinatorManager::until(Constants::WORKER_EXIT)->yield();
                if ($this->chan) {
                    $this->chan->close();
                }
            });
        }
    }

    /**
     * @var JobInterface|\Closure $handler
     */
    public function handle($handler) : void
    {
        if (!$this->chan) {
            $this->loop();
        }
        $this->chan->push(function () use ($handler)
        {
            $this->execute($handler);
        });
    }

    private function execute($handler) : void
    {
        try {
            is_callable($handler) ? $handler() : $handler->handle();
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf('PDO execute failed#'));
            throw $throwable;
        }
    }

    /**
     * Close the mysql.
     */
    public function close() : void
    {
        if (!Coroutine::inCoroutine()) {
            $this->PDO = null;
            return;
        }

        if (!$this->chan) {
            $this->loop();
        }

        $this->chan->push(function ()
        {
            $this->PDO = null;
        });
    }

}
