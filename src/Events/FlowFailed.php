<?php

namespace FlowPilot\LaravelFlowPilot\Events;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;

class FlowFailed
{
    public function __construct(
        public readonly FlowRun $run,
    ) {}
}
