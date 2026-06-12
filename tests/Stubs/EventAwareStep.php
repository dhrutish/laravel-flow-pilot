<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class EventAwareStep implements FlowStepContract
{
    public function handle(FlowContext $context): StepResult
    {
        /** @var TrialEndingSoon $event */
        $event = $context->payload('event');

        return StepResult::success([
            'name' => $event->name,
        ]);
    }
}
