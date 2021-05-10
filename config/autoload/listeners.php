<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
use App\Listener\DbQueryExecutedListener;
use App\Listener\DingListener;

return [
    DbQueryExecutedListener::class,
    DingListener::class,
];
