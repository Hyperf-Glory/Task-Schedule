<?php

declare(strict_types=1);

/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
use App\Middleware\AuthMiddleware;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag', 'App\Controller\IndexController@dag');
Router::addRoute(['GET', 'POST', 'HEAD'], '/test', 'App\Controller\IndexController@test');
Router::addRoute(['GET', 'POST', 'HEAD'], '/lua', 'App\Controller\IndexController@lua');
Router::addRoute(['GET', 'POST', 'HEAD'], '/io', 'App\Controller\IndexController@io');
Router::addRoute(['GET', 'POST', 'HEAD'], '/alert', 'App\Controller\IndexController@alert');
Router::addRoute(['GET', 'POST', 'HEAD'], '/queue', 'App\Controller\IndexController@queue');
Router::addRoute(['GET', 'POST', 'HEAD'], '/api/queue_status', 'App\Controller\IndexController@queueStatus');
Router::addRoute(['POST'], '/application', 'App\Controller\IndexController@application');
/* ---------------------- Dag  -------------------------*/
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag/conCurrentMySQL', 'App\Controller\DagController@conCurrentMySQL');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag/index', 'App\Controller\DagController@index');
/* ---------------------- Task ---------------------------------*/
Router::addRoute(['GET', 'POST', 'HEAD'], '/task/index', 'App\Controller\TaskController@index');
Router::addGroup('/task', function () {
    Router::addRoute(['POST'], 'create', 'App\Controller\TaskController@create');
    Router::addRoute(['POST'], 'abort', 'App\Controller\TaskController@abort');
    Router::addRoute(['POST'], 'retry', 'App\Controller\TaskController@retry');
    Router::addRoute(['GET'], 'detail', 'App\Controller\TaskController@detail');
}, [
    ['middleware' => [AuthMiddleware::class]],
]);

Router::get('/favicon.ico', function () {
    return '';
});
