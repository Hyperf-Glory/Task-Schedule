<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
return [
    'default' => [
        'enabled' => env('DING_ENABLED', true),
        'token' => env('DING_TOKEN', ''),
        'ssl_verify' => env('DING_SSL_VERIFY', true),
        'secret' => env('DING_SECRET', true),
        'options' => [
            'timeout' => env('DING_TIME_OUT', 2.0),
        ],
    ],
];
