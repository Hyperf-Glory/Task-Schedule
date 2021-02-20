<?php
declare(strict_types = 1);

namespace App\Job;

use App\Model\Task;
use App\Schedule\JobInterface;

class SimpleJob implements JobInterface
{

    /**
     * @var \App\Model\Task
     */
    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function handle() : void
    {
        echo "Task " . $this->task->id . PHP_EOL;
    }

    public function canRetry(int $attempt, $error) : bool
    {
        return $attempt < 5;
    }

    public function retryAfter(int $attempt) : int
    {
        return 0;
    }

    public function failed(int $id, array $payload) : void
    {
        echo "job#{$id} was failed.\n";
    }

    public function middleware() : array
    {
        return [JobMiddleware::class];
    }
}
