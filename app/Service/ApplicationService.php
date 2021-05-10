<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Service;

use App\Model\Application;
use Exception;
use Hyperf\Redis\Redis;

class ApplicationService
{
    protected $_applicationModel;

    protected $redis;

    public function __construct(Application $application, Redis $redis)
    {
        $this->_applicationModel = $application;
        $this->redis = $redis;
    }

    public function getApplicationInfo(string $key = null): array
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];

        try {
            if (empty($key)) {
                throw new Exception('APP KEY不能为空!');
            }

            $data = $this->redis->get($key);

            if (empty($data)) {
                $data = $this->_applicationModel->newModelQuery()->find()->where([
                    'is_deleted' => 0,
                    'app_key' => $key,
                    'status' => 1,
                ])->first();

                if (empty($data)) {
                    throw new Exception('APP KEY 输入有误!');
                }

                $this->redis->set($key, $data, 300);
            }

            $status = ['code' => 200, 'data' => $data, 'message' => ''];
        } catch (\Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        return $status;
    }
}
