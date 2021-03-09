<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Dag\Task\Task1;
use App\Dag\Task\Task2;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;

class DagController extends AbstractController
{
    public function conCurrentMySQL() : void
    {
        $dsn      = 'mysql:dbname=dag;host=120.79.187.246';
        $user     = 'root';
        $password = 'h9LcBXtX8Yxib4ov';
        try {
            $pdo = new \PDO($dsn, $user, $password);
            $pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            $c = new ConcurrentMySQLPattern($pdo, $this->logger);
            $c->beginTransaction();
            $dag     = new \Hyperf\Dag\Dag();
            $a       = \Hyperf\Dag\Vertex::make(function () use ($c)
            {
                $task = new Task1();
                return $task->Run($c);
            }, 'a');
            $b       = \Hyperf\Dag\Vertex::make(function ($results) use ($c)
            {
                $task = new Task2();
                return $task->Run($c);
            }, 'b');
            $d       = \Hyperf\Dag\Vertex::make(function ($results) use ($c, $a, $b)
            {
                if ($results[$a->key] && $results[$b->key]) {
                    return $c->commit();
                }
                return $c->rollback();
            }, 'd');
            $e       = \Hyperf\Dag\Vertex::make(function ($results) use ($c)
            {
                $c->close();
            }, 'e');
            $results = $dag
                ->addVertex($a)
                ->addVertex($b)
                ->addVertex($d)
                ->addVertex($e)
                ->addEdge($a, $b)
                ->addEdge($b, $d)
                ->addEdge($d, $e)
                ->run();
            dump($results);
        } catch (\PDOException $exception) {
            echo 'Connection failed: ' . $exception->getMessage();
        }
    }
}
