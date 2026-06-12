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
        'input',
        'output',
        'started_at',
        'completed_at',
        'failed_at',
        'failure_message',
    ];

    protected $casts = [
        'status' => FlowStepStatus::class,
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function flowRun(): BelongsTo
    {
        return $this->belongsTo(FlowRun::class);
    }
}
