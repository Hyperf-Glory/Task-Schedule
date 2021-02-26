<?php
declare(strict_types = 1);

namespace App\Dag\Interfaces;

use App\Kernel\Concurrent\ConcurrentMySQLPattern;
use PDO;

interface DagInterface
{
    /**
     *
     * @param ConcurrentMySQLPattern $pattern
     *
     * @return mixed
     */
    public function Run(ConcurrentMySQLPattern $pattern);

    public function isNext() : bool;
}
