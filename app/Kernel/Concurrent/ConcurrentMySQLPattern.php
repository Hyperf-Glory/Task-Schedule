<?php
declare(strict_types = 1);

namespace App\Kernel\Concurrent;

use Hyperf\Engine\Channel;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Psr\Log\LoggerInterface;

class ConcurrentMySQLPattern
{
    /**
     * @var ?\PDO
     */
    protected $pdo;

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
        $this->pdo    = $PDO;
        $this->logger = $logger;
        $this->chan   = new Channel(1);
    }

    public function loop() : void
    {
        Coroutine::create(function ()
        {
            while (true) {
                try {
                    $closure = $this->chan->pop();
                    if (!$closure) {
                        break;
                    }
                    $closure->call($this);
                } catch (\Throwable $e) {
                    $this->logger->error('Pdo error:' . $e->getMessage());
                    $this->pdo = null;
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

}
