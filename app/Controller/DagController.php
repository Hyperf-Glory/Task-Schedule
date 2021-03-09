<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Dag\Task\Task1;
use App\Dag\Task\Task2;
use App\Kernel\Concurrent\ConcurrentMySQLPattern;
use App\Model\Task;
use App\Model\VertexEdge;
use Hyperf\Dag\Dag;
use Hyperf\Dag\Vertex;

class DagController extends AbstractController
{

    /**
     * @var array<Vertex>
     */
    public $vertex;

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
        } catch (\PDOException $exception) {
            echo 'Connection failed: ' . $exception->getMessage();
        }
    }

    public function index() : void
    {
        $dag   = new Dag();
        $start = Vertex::make(function ()
        {
            sleep(1);
            echo "start\n";
        });
        $dag->addVertex($start);
        //TODO 查询 VertexEdge 任务流 VertexEdge::getQuery()->select('*')->where('workflow_id', '=', 1)->get();
        $task = Task::getQuery()->select('*')->where('workflow_id', '=', 1)->get();

        foreach ($task as $key => $value) {
            $this->vertex[$value->name] = Vertex::make(static function () use ($value)
            {
                sleep(1);
                echo $value->name . "\n";
            });
            $dag->addVertex($this->vertex[$value->name]);
        }

        $source = VertexEdge::query()->leftJoin('task', 'vertex_edge.task_id', '=', 'task.id')->select([
            'task.name',
            'vertex_edge.task_id',
            'vertex_edge.pid'
        ])->get()->toArray();
        $this->tree($dag, $source, 0);
        try {
            $dag->run();
        } catch (\Throwable $e) {
        }
    }

    private function tree(Dag $dag, array $source, int $pid = 0) : array
    {
        $tree = [];
        foreach ($source as $v) {
            if ($v['pid'] === $pid) {
                $v['children'] = $this->tree($dag, $source, $v['task_id']);
                if (empty($v['children'])) {
                    unset($v['children']);
                } else {
                    foreach ($v['children'] as $child) {
                        $dag->addEdge($this->vertex[$v['name']], $this->vertex[$child['name']]);
                    }
                }
                $tree[] = $v;
            }
        }
        return $tree;
    }
}
