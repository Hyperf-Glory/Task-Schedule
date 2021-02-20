<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Job\SimpleJob;
use App\Kernel\Nsq\Queue;
use App\Model\Task;
use App\Model\VertexEdge;
use Hyperf\Dag\Dag;
use Hyperf\Dag\Vertex;
use Hyperf\View\RenderInterface;
use Swoole\Coroutine;

class IndexController extends AbstractController
{

    public function index(RenderInterface $render)
    {
        return $render->render('index');
    }

    /**
     * @var array<Vertex>
     */
    public $vertex;

    public function dag()
    {
        $dag = new \Hyperf\Dag\Dag();
        $a   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "A\n";
        });
        $b   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "B\n";
        });
        $c   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "C\n";
        });
        $d   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "D\n";
        });
        $e   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "E\n";
        });
        $f   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "F\n";
        });
        $g   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "G\n";
        });
        $h   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "H\n";
        });
        $i   = \Hyperf\Dag\Vertex::make(function ()
        {
            sleep(1);
            echo "I\n";
        });
        $dag->addVertex($a)
            ->addVertex($b)
            ->addVertex($c)
            ->addVertex($d)
            ->addVertex($e)
            ->addVertex($f)
            ->addVertex($g)
            ->addVertex($h)
            ->addVertex($i)
            ->addEdge($a, $b)
            ->addEdge($a, $c)
            ->addEdge($a, $d)
            ->addEdge($b, $h)
            ->addEdge($b, $e)
            ->addEdge($b, $f)
            ->addEdge($c, $f)
            ->addEdge($c, $g)
            ->addEdge($d, $g)
            ->addEdge($h, $i)
            ->addEdge($e, $i)
            ->addEdge($f, $i)
            ->addEdge($g, $i);

        // 需要在协程环境下执行
        try {
            $dag->run();
        } catch (\Throwable $e) {
        }
    }

    public function test() : void
    {
        $dag   = new Dag();
        $start = Vertex::make(function ()
        {
            sleep(1);
            echo "start\n";
        });
        $dag->addVertex($start);
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

    public function queue()
    {
        $task = Task::find(1);

        $job = new SimpleJob($task);

        $queue = new Queue('queue');
        $queue->push($job, 0);
    }
}
