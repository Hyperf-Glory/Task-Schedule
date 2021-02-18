<?php

declare (strict_types = 1);
namespace App\Model;

/**
 * @property int $id
 * @property int $workflow_id
 * @property int $task_id
 * @property int $pid
 */
class VertexEdge extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vertex_edge';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'workflow_id', 'task_id', 'pid'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'workflow_id' => 'integer', 'task_id' => 'integer', 'pid' => 'integer'];
}
