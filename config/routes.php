<?php

declare(strict_types = 1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/dag', 'App\Controller\IndexController@dag');
Router::addRoute(['GET', 'POST', 'HEAD'], '/test', 'App\Controller\IndexController@test');
Router::addRoute(['GET', 'POST', 'HEAD'], '/queue', 'App\Controller\IndexController@queue');
Router::addRoute(['GET', 'POST', 'HEAD'], '/api/queue_status', 'App\Controller\IndexController@queueStatus');
Router::get('/favicon.ico', function ()
{
    return '';
});
