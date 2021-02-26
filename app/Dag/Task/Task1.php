<?php
declare(strict_types = 1);

namespace App\Dag\Task;

use App\Dag\Interfaces\DagInterface;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;
use PDO;

class Task1 implements DagInterface
{

    protected $task;

    public function __construct(string $task)
    {
        $this->task = $task;
    }

    /**
     * @inheritDoc
     */
    public function Run(ConcurrentMySQLPattern $pattern) : array
    {
        $sqlquery = "INSERT INTO edge (strat_vertex,end_vertex )VALUES (2,2)";
        if ($pattern->getPDO()->exec($sqlquery)) {
            echo "A new record has been inserted";
        }
        return [
            1,
            2,
            3,
            4,
            5
        ];
    }

    public function isNext() : bool
    {
        return true;
    }
}
