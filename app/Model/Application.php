<?php

declare(strict_types=1);
/**
 * This file is part of Task-Schedule.
 *
 * @license  https://github.com/Hyperf-Glory/Task-Schedule/main/LICENSE
 */
namespace App\Model;

/**
 * @property int            $id          自增ID
 * @property int            $is_deleted  是否删除
 * @property int            $status      是否审核 0:否 1:是
 * @property string         $app_name    应用名称
 * @property string         $app_key     APP KEY
 * @property string         $secret_key  SECRET KEY
 * @property int            $step        重试间隔(秒)
 * @property int            $retry_total 重试次数
 * @property string         $link_url    接口地址
 * @property string         $remark      备注信息
 * @property \Carbon\Carbon $created_at  创建时间
 * @property \Carbon\Carbon $updated_at  更新时间
 */
class Application extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'application';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'is_deleted',
        'status',
        'app_name',
        'app_key',
        'secret_key',
        'step',
        'retry_total',
        'link_url',
        'remark',
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
        'retry_total' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
