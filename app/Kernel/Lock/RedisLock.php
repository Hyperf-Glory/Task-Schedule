<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Kernel\Lock;

use Hyperf\Redis\Redis;
use Swoole\Coroutine;

class RedisLock
{
    protected const REDIS_LOCK_PREFIX = 'redis:lock:';

    /**
     * redis key前缀
     */
    private const REDIS_LOCK_KEY_PREFIX = 'redis:lock:';

    /**
     * @var array
     */
    private $lockedNames = [];

    /**
     * @var Redis
     */
    private $redis;

    /**
     * RedisLock constructor.
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    private function __clone()
    {
    }

    /**
     * 上锁
     *
     * @param string $name 锁名字
     * @param int $expire 锁有效期
     * @param int $retryTimes 重试次数
     * @param float|int $sleep 重试休息微秒
     *
     * @return mixed
     */
    public function lock(string $name, int $expire = 5, int $retryTimes = 10, float $sleep = 10000): bool
    {
        $lock = false;
        $retryTimes = max($retryTimes, 1);
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        while ($retryTimes-- > 0) {
            $kVal = microtime(true) + $expire;
            $lock = $this->getLock($key, $expire, $kVal); //上锁
            if ($lock) {
                $this->lockedNames[$key] = $kVal;
                break;
            }
            if (\Hyperf\Utils\Coroutine::inCoroutine()) {
                Coroutine::sleep((float) $sleep / 1000);
            } else {
                usleep($sleep);
            }
        }
        return $lock;
    }

    /**
     * 解锁
     *
     * @return mixed
     */
    public function unlock(string $name): bool
    {
        $script = <<<'LUA'
            local key = KEYS[1]
            local value = ARGV[1]

            if (redis.call('exists', key) == 1 and redis.call('get', key) == value) 
            then
                return redis.call('del', key)
            end

            return 0
LUA;
        $key = self::REDIS_LOCK_KEY_PREFIX . $name;
        if (isset($this->lockedNames[$key])) {
            $val = $this->lockedNames[$key];
            return $this->execLuaScript($script, [$key, $val]);
        }
        return false;
    }

    /**
     * 获取锁并执行.
     *
     * @param int $sleep
     *
     * @throws \Exception
     */
    public function run(callable $func, string $name, int $expire = 5, int $retryTimes = 10, $sleep = 10000): bool
    {
        if ($this->lock($name, $expire, $retryTimes, $sleep)) {
            try {
                $func();
            } catch (\Exception $e) {
                throw $e;
            } finally {
                $this->unlock($name);
            }
            return true;
        }

        return false;
    }

    /**
     * 获取锁
     *
     * @param $key
     * @param $expire
     * @param $value
     *
     * @return mixed
     */
    private function getLock($key, $expire, $value)
    {
        $script = <<<'LUA'
            local key = KEYS[1]
            local value = ARGV[1]
            local ttl = ARGV[2]

            if (redis.call('setnx', key, value) == 1) then
                return redis.call('expire', key, ttl)
            elseif (redis.call('ttl', key) == -1) then
                return redis.call('expire', key, ttl)
            end
            
            return 0
LUA;
        return $this->execLuaScript($script, [$key, $value, $expire]);
    }

    /**
     * 执行lua脚本.
     *
     * @param int $keyNum
     *
     * @return mixed
     */
    private function execLuaScript(string $script, array $params, $keyNum = 1)
    {
        $hash = $this->redis->script('load', $script);
        return $this->redis->evalSha($hash, $params, $keyNum);
    }
}
