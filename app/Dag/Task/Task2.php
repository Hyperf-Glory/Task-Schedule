<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
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
     * {@inheritDoc}
     */
    public function Run(ConcurrentMySQLPattern $pattern)
    {
        $sqlquery = 'DELETE FROM `edge` WHERE `edge_id` = 23';
        return $pattern->getPDO()->exec($sqlquery);
    }

    public function isNext(): bool
    {
        return $this->next;
    }
}
