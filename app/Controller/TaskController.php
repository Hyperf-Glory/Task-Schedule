<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Controller;

use App\Request\TaskRequest;
use App\Service\TaskService;
use Exception;
use Hyperf\Utils\Arr;
use Hyperf\View\Render;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class TaskController extends AbstractController
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function index(Render $render): ResponseInterface
    {
        return $render->render('task');
    }

    /**
     * 投递任务
     */
    public function create(TaskRequest $request): ResponseInterface
    {
        try {
            $data = $this->validator($request->all(), $request->rules(), $request->messages());
            $appKey = $request->getHeaderLine('app_key');
            $results = $this->taskService->create($appKey, $data);
            if (Arr::get($results, 'code') !== 200) {
                throw new Exception(Arr::get($results, 'message'));
            }

            return $this->response->success('', Arr::get($results, 'data'));
        } catch (Throwable $exception) {
            return $this->response->success($exception->getMessage());
        }
    }

    /**
     * 任务详情.
     */
    public function detail(TaskRequest $request): ResponseInterface
    {
        try {
            $data = $this->validator($request->all(), Arr::only($request->rules(), 'taskId'), $request->messages());
            $result = $this->taskService->detail($data['taskId']);
            if (Arr::get($result, 'code') !== 200) {
                throw new Exception(Arr::get($result, 'message'));
            }

            return $this->response->success('', Arr::get($result, 'data'));
        } catch (Throwable $exception) {
            return $this->response->success($exception->getMessage());
        }
    }

    /**
     * 任务重试.
     */
    public function retry(TaskRequest $request): ResponseInterface
    {
        try {
            $data = $this->validator($request->all(), Arr::only($request->rules(), 'taskId'), $request->messages());
            $appKey = $request->getHeaderLine('app_key');

        } catch (Throwable $exception) {
            return $this->response->success($exception->getMessage());
        }
    }
}
