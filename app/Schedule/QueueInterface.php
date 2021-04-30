<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Schedule;

interface QueueInterface
{
    /**
     * Get channel name of current queue.
     */
    public function getChannel(): string;

    /**
     * Get status of specific job.
     *
     * @throws \Throwable
     */
    public function getStatus(int $id): int;

    /**
     * Push an executable job message into queue.
     *
     * @param \Closure|JobInterface $message
     */
    public function push($message, float $defer = 0.0): void;

    /**
     * Remove specific job from current queue.
     *
     * @throws \Throwable
     */
    public function remove(int $id): void;

    /**
     * Release a job which was failed to execute.
     *
     * @throws \Throwable
     */
    public function release(int $id, int $delay = 0): void;

    /**
     * Fail a job.
     *
     * @throws \Throwable
     */
    public function failed(int $id, string $payload = null): void;

    /**
     * Get all failed jobs.
     *
     * @throws \Throwable
     */
    public function getFailed(): array;

    /**
     * Clear failed job.
     *
     * @throws \Throwable
     */
    public function clearFailed(int $id): void;

    /**
     * Reload failed job.
     *
     * @throws \Throwable
     */
    public function reloadFailed(int $id, int $delay = 0): void;

    /**
     * Clear current queue.
     *
     * @throws \Throwable
     */
    public function clear(): void;

    /**
     * Get status of current queue.
     *
     * @throws \Throwable
     */
    public function status(): array;

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isWaiting(int $id): bool;

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isReserved(int $id): bool;

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isDone(int $id): bool;

    /**
     * @param int $id of a job message
     *
     * @throws \Throwable
     */
    public function isFailed(int $id): bool;

    /**
     * Retry reserved job (only called when listener restart.).
     *
     * @throws \Throwable
     */
    public function retryReserved(): void;

    /**
     * Moved the expired job to waiting queue.
     *
     * @throws \Throwable
     */
    public function migrateExpired(): void;

    /**
     * Get job message from queue.
     *
     * @throws \Throwable
     */
    public function get(int $id): array;
}
