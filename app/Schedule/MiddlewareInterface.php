<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Schedule;

interface MiddlewareInterface
{
    /**
     * Handle current middleware.
     *
     * @param \App\Schedule\JobInterface $job
     * @param \Closure                   $next
     *
     * @return mixed
     */
    public function handle(JobInterface $job, \Closure $next);
}
