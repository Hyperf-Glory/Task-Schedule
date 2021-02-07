<?php

declare(strict_types = 1);

namespace App\Kernel\Log;

use App\Constants\Log;
use Hyperf\Utils\Context;
use Monolog\Processor\ProcessorInterface;

class AppendRequestIdProcessor implements ProcessorInterface
{
    public const TRACE_ID = 'log.trace.id';

    public function __invoke(array $record) : array
    {
        $record['context']['trace_id'] = Context::getOrSet(self::TRACE_ID, uniqid(md5(self::TRACE_ID), false));
        return $record;
    }
}
