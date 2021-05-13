<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Service;

use App\Kernel\Nsq\Queue;
use App\Model\Task;
use App\Model\TaskLog;
use App\Schedule\JobInterface;
use Exception;
use Han\Utils\Service;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Throwable;

class TaskService extends Service
{
    /**
     * @var \App\Service\ApplicationService
     */
    protected $applicationService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->applicationService = make(ApplicationService::class);
    }

    public function create(string $key, array $data = []): array
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];
        try {
            if (empty($key)) {
                throw new Exception('APP KEY 不能为空!');
            }

            if (empty($data)) {
                throw new Exception('提交数据不能为空!');
            }
            $application = $this->applicationService->getApplicationInfo($key);
            if (Arr::get($application, 'code') !== 200) {
                throw new Exception(Arr::get($application, 'message'));
            }
            $application = Arr::get($application, 'data');

            $taskNo = Arr::get($data, 'taskNo');
            $content = Arr::get($data, 'content');

            $runtime = Arr::get($data, 'runtime');
            $runtime = (! empty($runtime)) ? strtotime($runtime) : time();
            $count = Task::newModelInstance()->where([
                'is_deleted' => 0,
                'app_key' => $key,
                'task_no' => $taskNo,
                'status' => 0,
            ])->count();
            if ($count > 0) {
                throw new Exception('请勿重复提交！');
            }

            $appKey = Arr::get($application, 'app_key');
            $running = ($runtime <= time()) ? 1 : 0;
            $data = [
                'app_key' => $appKey,
                'task_no' => $taskNo,
                'status' => $running,
                'step' => Arr::get($application, 'step'),
                'runtime' => $runtime,
                'content' => $content,
                // $content  =  {"enable": true, "class": "\\Job\\SimpleJob\\","_params":{"startDate":"xx","endDate":"xxx"}},
                'created_at' => time(),
            ];
            $taskId = Task::newModelInstance()->insertGetId($data);
            if (empty($taskId)) {
                throw new Exception('任务记录写入失败!');
            }

            $delay = $runtime - time();
            $this->makeTask($taskId, $delay);
            $status = [
                'code' => 200,
                'data' => ['taskId' => $taskId],
                'message' => '',
            ];
        } catch (Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        return $status;
    }

    public function detail(int $taskId): array
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];

        try {
            if (empty($taskId)) {
                throw new Exception('任务ID不能为空!');
            }

            $result = Task::newModelInstance()->where(['is_deleted' => 0, 'id' => $taskId])->find();

            if (empty($result)) {
                throw new Exception('没有找到相任务记录!');
            }

            $logs = TaskLog::where(['task_id' => $taskId])->get();

            $data = [
                'taskId' => Arr::get($result, 'id'),
                'taskNo' => Arr::get($result, 'task_no'),
                'status' => Arr::get($result, 'status'),
                'step' => Arr::get($result, 'step'),
                'runtime' => Arr::get($result, 'runtime'),
                'content' => Arr::get($result, 'content'),
                'createdAt' => Arr::get($result, 'created_at'),
                'updatedAt' => Arr::get($result, 'updated_at'),
                'logs' => [],
            ];

            foreach ($logs as $k => $v) {
                $data['logs'][] = [
                    'retry' => Arr::get($v, 'retry'),
                    'remark' => Arr::get($v, 'remark'),
                    'createdAt' => Arr::get($v, 'created_at'),
                    'updatedAt' => Arr::get($v, 'updated_at'),
                ];
            }

            $status = ['code' => 200, 'data' => $data, 'message' => ''];
        } catch (Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        return $status;
    }

    /**
     * 任务重试.
     */
    public function retry(string $key, int $taskId): array
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];

        try {
            if (empty($key)) {
                throw new Exception('APP KEY 不能为空!');
            }

            if (empty($taskId)) {
                throw new Exception('任务ID不能为空!');
            }

            $task = Task::newModelInstance()->where(['is_deleted' => 0, 'id' => $taskId])->find();

            if (empty($task)) {
                throw new Exception('任务ID输入有误!');
            }

            $application = $this->applicationService->getApplicationInfo($key);

            if (Arr::get($application, 'code') !== 200) {
                throw new Exception(Arr::get($application, 'message'));
            }
            $this->makeTask($taskId);
            $status = ['code' => 200, 'data' => [], 'message' => ''];
        } catch (Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        return $status;
    }

    /**
     * @param float|int $delay
     *
     * @throws \ReflectionException
     */
    protected function makeTask(int $taskId, float $delay = 0): void
    {
        $task = Task::find($taskId);
        $content = Json::decode($task->content, true);
        $class = Arr::get($content, 'class');
        try {
            /**
             * @var JobInterface|\ReflectionClass $ref
             */
            $ref = new ReflectionClass($class);
            if (! $ref->implementsInterface(JobInterface::class)) {
                throw new \ReflectionException(sprintf(
                    'Class "%s" does not implement JobInterface',
                    get_class($ref)
                ));
            }
            $queue = new Queue('queue');
            $queue->push($ref, $delay);
        } catch (\ReflectionException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
