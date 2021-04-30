<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Dag\Interfaces;

use App\Kernel\Concurrent\ConcurrentMySQLPattern;

interface DagInterface
{
    /**
     * @return mixed
     */
    public function Run(ConcurrentMySQLPattern $pattern);

    public function isNext(): bool;
}
