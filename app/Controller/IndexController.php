<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Component\Hashids\Hashids;
use App\Job\SimpleJob;
use App\Kernel\Nsq\Queue;
use App\Kernel\Redis\Lua\Incr;
use App\Model\Task;
use App\Model\VertexEdge;
use Hyperf\Dag\Dag;
use Hyperf\Dag\Vertex;
use Hyperf\View\RenderInterface;
use phpseclib\Crypt\Hash;
use Psr\Http\Message\ResponseInterface;

class IndexController extends AbstractController
{

    public function index(RenderInterface $render) : ResponseInterface
    {
        return $render->render('index');
    }

    public function queueStatus() : ?ResponseInterface
    {
        $queue = new Queue('queue');
        try {
            [$waiting, $reserved, $delayed, $done, $failed, $total] = $queue->status();
            $status = compact('waiting', 'reserved', 'failed', 'delayed', 'done', 'total');
            $pie    = [];
            foreach ($status as $tag => $item) {
                if ('total' !== $tag) {
                    $pie[] = [
                        'status' => $tag,
                        'value'  => $item,
                    ];
                }
            }

            $line = array_merge(['time' => date('Y-m-d H:i:s')], $status);
            return $this->response->success('获取成功!', [
                'pie'  => $pie,
                'line' => $line
            ]);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
        return null;
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

    public function lua()
    {
        $script = new Incr($this->container);
        $id     = $script->eval(['short#link', 24 * 60 * 60]);
        if (!$this->container->has(Hashids::class)) {
            $this->container->set(Hashids::class, new Hashids());
        }
        $hashids = $this->container->get(Hashids::class);
        return $hashids->encode($id);
    }
}
