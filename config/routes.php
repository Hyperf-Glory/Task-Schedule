<?php

declare(strict_types = 1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag', 'App\Controller\IndexController@dag');
Router::addRoute(['GET', 'POST', 'HEAD'], '/test', 'App\Controller\IndexController@test');
Router::addRoute(['GET', 'POST', 'HEAD'], '/lua', 'App\Controller\IndexController@lua');
Router::addRoute(['GET', 'POST', 'HEAD'], '/io', 'App\Controller\IndexController@io');
Router::addRoute(['GET', 'POST', 'HEAD'], '/queue', 'App\Controller\IndexController@queue');
Router::addRoute(['GET', 'POST', 'HEAD'], '/api/queue_status', 'App\Controller\IndexController@queueStatus');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag/conCurrentMySQL', 'App\Controller\DagController@conCurrentMySQL');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag/index', 'App\Controller\DagController@index');
Router::get('/favicon.ico', function ()
{
    return '';
});
