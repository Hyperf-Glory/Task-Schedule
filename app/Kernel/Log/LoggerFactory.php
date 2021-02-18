<?php

declare(strict_types = 1);

namespace App\Kernel\Log;

use Hyperf\Logger\LoggerFactory as HyperfLoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container) : LoggerInterface
    {
        return $container->get(HyperfLoggerFactory::class)->make();
    }
}
