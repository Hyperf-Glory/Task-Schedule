<?php
declare(strict_types = 1);

namespace App\Controller;

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
            $pdo        = new \PDO($dsn, $user, $password);
            $concurrent = new ConcurrentMySQLPattern($pdo, $this->logger);
            \Hyperf\Utils\Coroutine::create(function () use ($concurrent)
            {

            });
            \Hyperf\Utils\Coroutine::create(function () use ($concurrent)
            {
                $concurrent->handle(static function ()
                {
                    echo '执行成功';
                });
            });
        } catch (\PDOException $exception) {
            echo 'Connection failed: ' . $exception->getMessage();
        }
    }
}
