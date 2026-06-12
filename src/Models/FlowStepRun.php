<?php

namespace FlowPilot\LaravelFlowPilot\Models;

use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowStepRun extends Model
{
    protected $table = 'flow_pilot_steps';

    protected $fillable = [
        'flow_run_id',
        'name',
        'class',
        'status',
        'position',
        'attempts',
        'max_attempts',
        'input',
        'output',
        'metadata',
        'started_at',
        'completed_at',
        'failed_at',
        'skipped_at',
        'failure_message',
    ];

    protected $casts = [
        'status' => FlowStepStatus::class,
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'input' => 'array',
        'output' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    public function flowRun(): BelongsTo
    {
        return $this->belongsTo(FlowRun::class);
    }
}
