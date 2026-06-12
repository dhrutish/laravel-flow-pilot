<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Flow;

class TestFlow extends Flow
{
    public function name(): string
    {
        return 'test-flow';
    }

    public function define(): void
    {
        $this->step('first', FirstStep::class);
        $this->step('second', SecondStep::class);
    }
}
