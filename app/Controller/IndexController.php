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

    public function queue() : void
    {
        $task = Task::find(1);

        $job = new SimpleJob($task);

        $queue = new Queue('queue');
        $queue->push($job);
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

    public function io() : string
    {
        dump($this->request->input('io'));
        return (string)$this->request->input('io');
    }
}
