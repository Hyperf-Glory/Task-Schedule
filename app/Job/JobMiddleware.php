<?php
declare(strict_types = 1);

namespace App\Job;

use App\Schedule\JobInterface;
use App\Schedule\MiddlewareInterface;

class JobMiddleware implements MiddlewareInterface
{
    public function handle(JobInterface $job, \Closure $next)
    {
        throw new \InvalidArgumentException('参数无效');
    }
}
