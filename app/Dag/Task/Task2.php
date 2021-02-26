<?php
declare(strict_types = 1);

namespace App\Dag\Task;

use App\Dag\Interfaces\DagInterface;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;

class Task2 implements DagInterface
{
    /**
     * @var bool
     */
    public $next;

    /**
     * @inheritDoc
     */
    public function Run(ConcurrentMySQLPattern $pattern) : void
    {
        $sqlquery = "DELETE FROM edge WHERE edge_id = 1";
        if ($pattern->getPDO()->exec($sqlquery)) {
            echo "A new record has been deleted.";
        }
        $this->next = true;
    }

    public function isNext() : bool
    {
        return $this->next;
    }
}
