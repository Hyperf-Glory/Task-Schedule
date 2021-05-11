<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Middleware;

use App\Kernel\Http\Response;
use App\Service\ApplicationService;
use Exception;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var ApplicationService
     */
    private $_applicationService;

    /**
     * @var Response
     */
    private $httpResponse;

    /**
     * 初始化方法.
     */
    public function __construct(ContainerInterface $container, Response $httpResponse)
    {
        $this->container = $container;
        $this->httpResponse = $httpResponse;
    }

    /**
     * 处理方法.
     *
     * @param ServerRequestInterface $request 数据请求
     * @param RequestHandlerInterface $handler 处理方法
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];

        try {
            $validator = $this->checkSignature($request);

            if (Arr::get($validator, 'code') !== 200) {
                throw new Exception(Arr::get($validator, 'message'));
            }

            $status = ['code' => 200, 'data' => [], 'message' => ''];
        } catch (Throwable $e) {
            $status['message'] = $e->getMessage();
        }

        return ($status['code'] === 0) ? $this->httpResponse->response()
            ->withBody(new SwooleStream(Json::encode($status))) : $handler->handle($request);
    }

    /**
     * 校验签名.
     */
    private function checkSignature(ServerRequestInterface $request): array
    {
        $status = ['code' => 0, 'data' => [], 'message' => ''];

        try {
            $data = $request->getParsedBody();

            $appKey = $request->getHeaderLine('app_key');
            $nonceStr = $request->getHeaderLine('nonce_str');
            $timestamp = $request->getHeaderLine('timestamp');
            $signature = $request->getHeaderLine('signature');
            $version = $request->getHeaderLine('version');

            if (empty($appKey)) {
                throw new Exception('APP KEY不能为空！');
            }

            // 获取加密密钥
            $application = $this->_applicationService->getApplicationInfo($appKey);
            $secretKey = Arr::get($application, 'data.secret_key');

            if (empty($secretKey)) {
                throw new Exception('应用不存在或者未审核！');
            }

            if (empty($nonceStr) || strlen($nonceStr) < 6) {
                throw new Exception('随机字符串不能小于6位！');
            }

            if (empty($timestamp)) {
                throw new Exception('请求日期不能为空！');
            }

            $now = strtotime($timestamp);

            if (empty($now)) {
                throw new Exception('请求日期格式有误！');
            }

            $now = time() - $now;

            if ($now > 300 || $now < -300) {
                throw new Exception('请求时间与系统偏差过大！');
            }

            if (empty($signature)) {
                throw new Exception('签名数据不能为空！');
            }

            if (empty($version)) {
                throw new Exception('版本号不能为空！');
            }

            if ($version !== '1.0') {
                throw new Exception('版本号输入有误！');
            }

            $data['app_key'] = $appKey;
            $data['nonce_str'] = $nonceStr;
            $data['version'] = $version;
            $data['timestamp'] = $timestamp;

            ksort($data);

            $str = http_build_query($data, '', '&');
            $str = urldecode($str);

            $signStr = md5($str . $secretKey);

            if ($signature !== $signStr) {
                throw new Exception('签名校验失败！');
            }

            $status = ['code' => 200, 'data' => [], 'message' => ''];
        } catch (Exception $e) {
            $status['message'] = $e->getMessage();
        }

        return $status;
    }
}
