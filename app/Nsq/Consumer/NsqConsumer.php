<?php

declare(strict_types=1);

namespace App\Nsq\Consumer;

use Hyperf\Nsq\AbstractConsumer;
use Hyperf\Nsq\Annotation\Consumer;
use Hyperf\Nsq\Message;
use Hyperf\Nsq\Result;

/**
 * @Consumer(topic="task-schedule", channel="task-schedule", name ="NsqConsumer", nums=1)
 */
class NsqConsumer extends AbstractConsumer
{
    public function consume(Message $message): ?string
    {
        var_dump($message->getBody());

        return Result::ACK;
    }
}
