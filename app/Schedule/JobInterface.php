<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Schedule;

interface JobInterface
{
    /**
     * Execute current job.
     *
     * @return mixed
     */
    public function handle(): void;

    /**
     * Determine whether current job can retry if fail.
     *
     * @param int $attempt
     * @param     $error
     *
     * @return bool
     */
    public function canRetry(int $attempt, $error): bool;

    /**
     * Get current job's next execution unix time after failed.
     */
    public function retryAfter(int $attempt): int;

    /**
     * After failed, this function will be called.
     */
    public function failed(int $id, array $payload): void;

    /**
     * Get the middleware the job should pass through.
     *
     * @return MiddlewareInterface[]
     */
    public function middleware(): array;
}
