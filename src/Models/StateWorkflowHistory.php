<?php namespace Ringierimu\StateWorkflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $model_name
 * @property int $model_id
 * @property string $transition
 * @property string $from
 * @property string $to
 * @property int $user_id
 *
 * Class StateWorkflowHistory
 * @package Ringierimu\StateWorkflow\Models
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();

    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }
}
