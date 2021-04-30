<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Job;

use App\Schedule\Exception\ScheduleException;
use App\Schedule\JobInterface;
use App\Schedule\MiddlewareInterface;

class JobMiddleware implements MiddlewareInterface
{
    public function handle(JobInterface $job, \Closure $next)
    {
        if (! $job instanceof JobInterface) {
            throw new ScheduleException('参数无效');
        }
        return $next($job);
    }
}
