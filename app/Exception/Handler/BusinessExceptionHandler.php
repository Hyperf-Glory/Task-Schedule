<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Exception\Handler;

use App\Constants\ErrorCode;
use App\Exception\BusinessException;
use App\Kernel\Http\Response;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\ExitException;
use Throwable;

class BusinessExceptionHandler extends ExceptionHandler
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->response = $container->get(Response::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof HttpException) {
            $this->stopPropagation();

            return $this->response->handleException($throwable);
        }

        if ($throwable instanceof ExitException) {
            return $this->response->success();
        }

        if ($throwable instanceof ValidationException) {
            return $this->response->fail(ErrorCode::SERVER_ERROR, $throwable->validator->errors()->first());
        }

        if ($throwable instanceof BusinessException) {
            $this->logger->warning(format_throwable($throwable));

            return $this->response->fail($throwable->getCode(), $throwable->getMessage());
        }

        $this->logger->error(format_throwable($throwable));

        return $response->withHeader('Server', 'SocketIO')->withStatus(500)
            ->withBody(new SwooleStream('Internal Server Error.'));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
