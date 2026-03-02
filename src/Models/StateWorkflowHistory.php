<?php

namespace Ringierimu\StateWorkflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * @property string $model_name
 * @property int $model_id
 * @property string $transition
 * @property string $from
 * @property string $to
 * @property int $user_id
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
     * @return MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(config('workflow.setup.user_class'));
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }
}
