<?php

namespace FlowPilot\LaravelFlowPilot\Events;

use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;

class FlowStepFailed
{
    public function __construct(
        public readonly FlowStepRun $stepRun,
    ) {}
}
