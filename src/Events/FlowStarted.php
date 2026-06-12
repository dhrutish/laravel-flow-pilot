<?php

namespace FlowPilot\LaravelFlowPilot\Events;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;

class FlowStarted
{
    public function __construct(
        public readonly FlowRun $run,
    ) {}
}
