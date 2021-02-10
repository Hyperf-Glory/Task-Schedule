<?php
declare(strict_types = 1);
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

