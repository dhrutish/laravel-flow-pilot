<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class FirstStep implements FlowStepContract
{
    public function handle(FlowContext $context): StepResult
    {
        return StepResult::success([
            'greeting' => 'Hello, '.$context->get('name', 'World'),
        ]);
    }
}
