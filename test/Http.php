<?php

declare(strict_types=1);
/**
 *
 * This is my open source code, please do not use it for commercial applications.
 *
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code
 *
 * @author CodingHePing<847050412@qq.com>
 * @link   https://github.com/Hyperf-Glory/socket-io
 */
Swoole\Coroutine\run(function () {
    for ($i = 0; $i < 1000000; ++$i) {
        \Swoole\Coroutine::create(function () {
            Swoole\Coroutine::sleep(1);
        });
    }
});
