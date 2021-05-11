<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
use Han\Utils\Middleware\DebugMiddleware;

return [
    'http' => [
        DebugMiddleware::class,
    ],
];
