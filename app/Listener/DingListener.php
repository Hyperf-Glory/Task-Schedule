<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Listener;

use App\Event\Message;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use HyperfGlory\AlertManager\DingTalk;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DingListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql', 'sql');
    }

    /**
     * {@inheritDoc}
     */
    public function listen(): array
    {
        return [
            Message::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function process(object $event)
    {
        if ($event instanceof Message) {
            /**
             * @var DingTalk $ding
             */
            $ding = make(DingTalk::class);
            if (method_exists($ding, $event->type)) {
                $ding->{$event->type}($event->message);

                return;
            }
            $this->logger->error(sprintf('DingTalk Action[%s] UnKnown ', $event->type));
        }
    }
}
