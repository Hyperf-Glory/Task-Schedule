# Task-Schedule

基于Hyperf开发的任务调度系统

基于 Hyperf 的一个异步队列库，可弹性伸缩的工作进程池，工作进程协程支持.

## 特性

- 默认 Nsq 驱动
- 秒级延时任务
- 自定义重试次数和时间
- 自定义错误回调
- 支持任务执行中间件
- 自定义队列快照事件
- 弹性多进程消费
- 工作进程协程支持
- 漂亮的仪表盘
- 任务编排协程安全的单连接模式
- dag任务编排

## 环境

- PHP 7.4+
- Swoole 4.6+
- Redis 5.0+ (redis 驱动)

## TODO

- 分布式支持

## 案例

1.投递任务

```php
use App\Model\Task;
use App\Job\SimpleJob;
use App\Kernel\Nsq\Queue;
class Example{
     /**
     * @desc 测试job队列功能
     */
    public function queue() : void
    {
        $task = Task::find(1);

        $job = new SimpleJob($task);

        $queue = new Queue('queue');
        $queue->push($job);
    }
}
```
2.仪表盘
![img.png](img.png)
