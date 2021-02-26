<?php
declare(strict_types = 1);

namespace App\Kernel\Concurrent;

use App\Dag\Interfaces\DagInterface;
use App\Kernel\Concurrent\Exception\MySQLRuntimeException;
use Hyperf\Engine\Channel;
use Hyperf\Utils\Coordinator\Constants;
use Hyperf\Utils\Coordinator\CoordinatorManager;
use Hyperf\Utils\Coroutine;
use Psr\Log\LoggerInterface;
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
     *
     *
     * @param DagInterface $dag
     *
     * @deprecated
     *
     */
    public function handle(DagInterface $dag) : void
    {
        if (!$this->chan) {
            $this->loop();
        }
        $this->chan->push(function () use ($dag)
        {
            $dag->Run();
        });
    }

    public function commit() : void
    {
        if (!$this->chan) {
            $this->loop();
        }
        if ($this->PDO->inTransaction()) {
            $this->PDO->commit();
        }
        throw new MySQLRuntimeException(sprintf('PDO does not open a transaction#.'));
    }

    public function rollback() : void
    {
        if (!$this->chan) {
            $this->loop();
        }
        if ($this->PDO->inTransaction()) {
            $this->PDO->rollBack();
        }
        throw new MySQLRuntimeException(sprintf('PDO does not open a transaction#.'));
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

    /**
     * @return null|\PDO
     */
    public function getPDO() : ?\PDO
    {
        return $this->PDO;
    }

}
