<?php

namespace FlowPilot\LaravelFlowPilot\Contracts;

use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

interface FlowStepContract
{
    public function handle(FlowContext $context): StepResult;
}
