<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Component\Hashids\Hashids;
use App\Job\SimpleJob;
use App\Kernel\Nsq\Queue;
use App\Kernel\Redis\Lua\Incr;
use App\Model\Task;
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

    /**
     * 测试job队列功能
     */
    public function queue() : void
    {
        $task = Task::find(1);

        $job = new SimpleJob($task);

        $queue = new Queue('queue');
        $queue->push($job);
    }

    /**
     * 测试lua脚本
     * @return mixed
     */
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
