<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Dag\Task\Task1;
use App\Dag\Task\Task2;
use App\Dag\Task\Task3;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;
use App\Kernel\Context\Coroutine;

class DagController extends AbstractController
{
    public function conCurrentMySQL() : void
    {
        $dsn      = 'mysql:dbname=dag;host=101.200.75.54';
        $user     = 'root';
        $password = 'h9LcBXtX8Yxib4ov';
        //TODO 待重新设计 事务的回滚和提交
        try {
            $pdo = new \PDO($dsn, $user, $password);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            $c = new ConcurrentMySQLPattern($pdo, $this->logger);
            $c->beginTransaction();
            //TODO DAG
            \Hyperf\Utils\Coroutine::create(static function () use ($c)
            {
                $task = new Task1();
                $task->Run($c);
            });
            \Hyperf\Utils\Coroutine::create(static function () use ($c)
            {
                $task = new Task2();
                $task->Run($c);
            });

            \Hyperf\Utils\Coroutine::create(static function () use ($c)
            {
                $task = new Task3();
                $task->Run($c);
            });
        } catch (\PDOException $exception) {
            echo 'Connection failed: ' . $exception->getMessage();
        }
    }
}
