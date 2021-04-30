<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Model;

/**
 * @property int            $id         主键ID
 * @property int            $is_deleted 是否删除
 * @property int            $status     任务状态 0:待处理 1:处理中 2:已处理 3:已取消
 * @property string         $app_key    APP KEY
 * @property string         $task_no    任务编号
 * @property int            $step       重试间隔(秒)
 * @property int            $runtime    执行时间
 * @property string         $content    任务内容
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class Task extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'is_deleted',
        'status',
        'app_key',
        'task_no',
        'step',
        'runtime',
        'content',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'is_deleted' => 'integer',
        'status' => 'integer',
        'step' => 'integer',
        'runtime' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
