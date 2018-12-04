<?php namespace Oneafricamedia\StateWorkflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class StateWorkflowHistory
 * @package Oneafricamedia\StateWorkflow\Models
 */
class StateWorkflowHistory extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'model_name',
        'model_id',
        'transition',
        'from',
        'to',
        'user_id'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function model()
    {
        return $this->belongsTo("$this->model_name");
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
