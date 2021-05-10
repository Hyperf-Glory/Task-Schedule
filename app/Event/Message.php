<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Event;

class Message
{
    public $message;

    public $type;

    public function __construct($message, $type = 'text')
    {
        $this->message = $message;
        $this->type = $type;
    }
}
