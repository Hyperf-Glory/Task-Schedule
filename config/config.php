<?php

declare(strict_types = 1);

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

return [
    'app_name'                   => env('APP_NAME', 'skeleton'),
    'app_env'                    => env('APP_ENV', 'dev'),
    'scan_cacheable'             => env('SCAN_CACHEABLE', true),
    StdoutLoggerInterface::class => [
        'log_level' => [
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::DEBUG,
            LogLevel::EMERGENCY,
            LogLevel::ERROR,
            LogLevel::INFO,
            LogLevel::NOTICE,
            LogLevel::WARNING,
        ],
    ],
];
