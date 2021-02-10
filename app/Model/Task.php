<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property int $workflow_id 
 * @property string $name 
 * @property string $status 
 * @property string $result 
 * @property string $parents 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
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
    protected $fillable = ['id', 'workflow_id', 'name', 'status', 'result', 'parents', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'workflow_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}