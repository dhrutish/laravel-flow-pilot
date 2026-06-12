<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class SecondStep implements FlowStepContract
{
    public function handle(FlowContext $context): StepResult
    {
        $firstOutput = $context->stepOutput('first');

        return StepResult::success([
            'message' => ($firstOutput['greeting'] ?? 'Hello').' from step two.',
        ]);
    }
}
