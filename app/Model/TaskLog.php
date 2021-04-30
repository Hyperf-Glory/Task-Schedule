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
 * @property int            $task_id    任务ID
 * @property int            $retry      重试次数
 * @property string         $remark     备注信息
 * @property \Carbon\Carbon $created_at 创建时间
 * @property \Carbon\Carbon $updated_at 更新时间
 */
class TaskLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'is_deleted', 'task_id', 'retry', 'remark', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer',
        'is_deleted' => 'integer',
        'task_id' => 'integer',
        'retry' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
