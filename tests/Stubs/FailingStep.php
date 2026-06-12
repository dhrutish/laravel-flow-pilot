<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class FailingStep implements FlowStepContract
{
    public function handle(FlowContext $context): StepResult
    {
        return StepResult::failure('The step failed.', [
            'api_key' => 'should-not-be-stored',
        ]);
    }
}
