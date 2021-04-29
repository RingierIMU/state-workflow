<?php

namespace Ringierimu\StateWorkflow\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $model_name
 * @property int    $model_id
 * @property string $transition
 * @property string $from
 * @property string $to
 * @property int    $user_id
 *
 * Class StateWorkflowHistory
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
        'context',
        'user_id',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'context' => 'array',
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
        return $this->belongsTo(config('workflow.setup.user_class'));
    }
}
