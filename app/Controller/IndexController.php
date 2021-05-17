<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Controller;

use App\Component\Hashids\Hashids;
use App\Job\SimpleJob;
use App\Kernel\Nsq\Queue;
use App\Kernel\Redis\Lua\Incr;
use App\Model\Task;
use App\Request\TaskRequest;
use App\Service\ApplicationService;
use Exception;
use Hyperf\Dag\Vertex;
use Hyperf\Utils\Arr;
use Hyperf\View\RenderInterface;
use HyperfGlory\AlertManager\DingTalk;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class IndexController extends AbstractController
{
    /**
     * @var array<Vertex>
     */
    public $vertex;

    /**
     * @var ApplicationService
     */
    protected $application;

    public function __construct()
    {
        $this->application = make(ApplicationService::class);
    }

    public function index(RenderInterface $render): ResponseInterface
    {
        return $render->render('index');
    }

    public function queueStatus(): ?ResponseInterface
    {
        $queue = new Queue('queue');
        try {
            [$waiting, $reserved, $delayed, $done, $failed, $total] = $queue->status();
            $status = compact('waiting', 'reserved', 'failed', 'delayed', 'done', 'total');
            $pie = [];
            foreach ($status as $tag => $item) {
                if ($tag !== 'total') {
                    $pie[] = [
                        'status' => $tag,
                        'value' => $item,
                    ];
                }
            }

            $line = array_merge(['time' => date('Y-m-d H:i:s')], $status);

            return $this->response->success('获取成功!', [
                'pie' => $pie,
                'line' => $line,
            ]);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage());
        }

        return null;
    }

    /**
     * 测试job队列功能.
     */
    public function queue(): void
    {
        try {
            $task = Task::find(1);
            $job = new SimpleJob($task);
            $queue = new Queue('queue');
            $queue->push($job);
        } catch (Throwable $e) {
            dump($e->getMessage());
        }
    }

    /**
     * 测试lua脚本.
     *
     * @return mixed
     */
    public function lua()
    {
        $script = new Incr($this->container);
        $id = $script->eval(['short#link', 24 * 60 * 60]);
        if (! $this->container->has(Hashids::class)) {
            $this->container->set(Hashids::class, new Hashids());
        }
        $hashids = $this->container->get(Hashids::class);

        return $hashids->encode($id);
    }

    public function alert()
    {
        try {
            /**
             * @var DingTalk $dingtalk
             */
            $dingtalk = make(DingTalk::class);
            $dingtalk->text('呵呵');
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    /**
     * 创建应用.
     */
    public function application(TaskRequest $request): ?ResponseInterface
    {
        try {
            $data = $this->validator($request->all(), $request->rules(), $request->messages());
            $results = $this->application->create($data);
            if (Arr::get($results, 'code') !== 200) {
                throw new Exception(Arr::get($results, 'message'));
            }

            return $this->response->success('', Arr::get($results, 'data'));
        } catch (Throwable $exception) {
            return $this->response->success($exception->getMessage());
        }
    }
}
