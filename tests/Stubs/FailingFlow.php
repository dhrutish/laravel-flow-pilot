<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Flow;

class FailingFlow extends Flow
{
    public function name(): string
    {
        return 'failing-flow';
    }

    public function define(): void
    {
        $this->step('failing', FailingStep::class);
    }
}
