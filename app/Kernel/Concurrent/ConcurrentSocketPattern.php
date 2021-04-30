<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Kernel\Concurrent;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Socket;

class ConcurrentSocketPattern
{
    protected $chan;

    protected $socket;

    public function __construct()
    {
        $this->chan = new Channel();
        $this->socket = new Socket(AF_INET, SOCK_STREAM, 0);
    }

    public function loop(): void
    {
        while (true) {
            $closure = $this->chan->pop();
            if (! $closure) {
                break;
            }
            $closure->call($this);
        }
    }

    public function send($str): void
    {
        $cont = new Channel();
        $this->chan->push(function () use ($cont, $str) {
            $this->socket->send($str);
            $cont->push(true);
        });
        $cont->pop();
    }

    public function recv()
    {
        $cont = new Channel();
        $this->chan->push(function () use ($cont) {
            $str = $this->socket->recv();
            $cont->push($str);
        });
        return $cont->pop();
    }

    public function connect(): void
    {
        $cont = new Channel();
        $this->chan->push(function () use ($cont) {
            $this->socket->connect('localhost', 2701);
            $cont->push(true);
        });
        $cont->pop();
    }

    /**
     * 退出逻辑.
     */
    public function quit(): void
    {
        $this->socket->close();
        $this->chan->close();
    }
}

\Swoole\Coroutine\Run(function () {
    $c = new ConcurrentSocketPattern();
    Coroutine::create(function () use ($c) {
        $c->loop();
    });
    Coroutine::create(function () use ($c) {
        $c->connect();
    });
    Coroutine::create(function () use ($c) {
        $c->send('hello');
    });
    Coroutine::create(function () use ($c) {
        return $c->recv();
    });
    Coroutine::create(function () use ($c) {
        $c->quit();
    });
    sleep(3);
});
