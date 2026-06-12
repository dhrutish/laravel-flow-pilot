<?php

namespace FlowPilot\LaravelFlowPilot\Models;

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlowRun extends Model
{
    protected $table = 'flow_pilot_runs';

    protected $fillable = [
        'uuid',
        'flow_name',
        'flow_class',
        'status',
        'trigger_type',
        'trigger_name',
        'payload',
        'metadata',
        'started_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'failure_message',
    ];

    protected $casts = [
        'status' => FlowRunStatus::class,
        'payload' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function stepRuns(): HasMany
    {
        return $this->hasMany(FlowStepRun::class)->orderBy('position');
    }
}
