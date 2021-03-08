<?php
declare(strict_types = 1);

namespace App\Dag\Task;

use App\Dag\Interfaces\DagInterface;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;
use PDO;

class Task1 implements DagInterface
{

    /**
     * @inheritDoc
     */
    public function Run(ConcurrentMySQLPattern $pattern)
    {
        $start    = random_int(1, 999);
        $end      = random_int(999, 99999);
        $sqlquery = "INSERT INTO `edge` (`start_vertex`,`end_vertex`) VALUES ({$start},{$end})";
        return $pattern->getPDO()->exec($sqlquery);
    }

    public function isNext() : bool
    {
        return true;
    }
}
