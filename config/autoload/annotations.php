<?php

declare(strict_types = 1);

use Hyperf\Utils\Coroutine;

return [
    'scan' => [
        'paths'              => [
            BASE_PATH . '/app',
        ],
        'ignore_annotations' => [
            'mixin',
        ],
        'class_map'          => [
            // 需要映射的类名 => 类所在的文件地址
            Coroutine::class => BASE_PATH . '/app/Kernel/ClassMap/Coroutine.php',
        ],
    ],
];
