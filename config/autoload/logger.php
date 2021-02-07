<?php

declare(strict_types = 1);

use App\Kernel\Log\AppendRequestIdProcessor;
use Monolog\Formatter;

return [
    'default' => [
        'handler'    => [
            'class'       => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                'level'  => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter'  => [
            'class'       => Formatter\JsonFormatter::class,
            'constructor' => [
                'batchMode'     => Formatter\JsonFormatter::BATCH_MODE_JSON,
                'appendNewline' => true,
            ],
        ],
        'processors' => [
            [
                'class' => AppendRequestIdProcessor::class,
            ],
        ],
    ],
    'sql'     => [
        'handler'    => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/sql/sql.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter'  => [
            'class'       => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format'                => null,
                'dateFormat'            => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
        'processors' => [
            [
                'class' => AppendRequestIdProcessor::class,
            ],
        ],
    ],
];
