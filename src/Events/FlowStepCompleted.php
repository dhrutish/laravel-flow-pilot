<?php

namespace FlowPilot\LaravelFlowPilot\Events;

use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;

class FlowStepCompleted
{
    public function __construct(
        public readonly FlowStepRun $stepRun,
    ) {}
}
