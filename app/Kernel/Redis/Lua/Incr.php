<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Kernel\Redis\Lua;

use Hyperf\Redis\Lua\Script;

class Incr extends Script
{
    public function getScript(): string
    {
        return <<<'LUA'
 local current = redis.call('incr',KEYS[1]);
                local t = redis.call('ttl',KEYS[1]);
                if t == -1 then
                redis.call('expire',KEYS[1],ARGV[1])
                end;
                return current;
LUA;
    }

    public function format($data)
    {
        if (is_numeric($data)) {
            return $data;
        }
        return null;
    }

    protected function getKeyNumber(array $arguments): int
    {
        return 1;
    }
}
