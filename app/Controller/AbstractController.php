<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Controller;

use App\Kernel\Http\Response;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractController
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @Inject
     * @var Response
     */
    protected $response;

    /**
     * @Inject
     * @var \Hyperf\Contract\StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @Inject
     * @var ValidatorFactoryInterface
     */
    protected $validationFactory;

    protected function validator(array $params, array $rules = [], array $messages = []): array
    {
        $validator = $this->validationFactory->make($params, $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
