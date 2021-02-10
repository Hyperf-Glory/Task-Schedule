<?php

declare (strict_types=1);
namespace App\Model;

/**
 * @property int $id 
 * @property string $name 
 * @property int $is_active 
 * @property string $status 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 */
class Workflow extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workflow';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'is_active', 'status', 'created_at', 'updated_at'];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'is_active' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}