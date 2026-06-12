<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Flow;

class EventTriggeredFlow extends Flow
{
    public function name(): string
    {
        return 'event-triggered-flow';
    }

    public function define(): void
    {
        $this
            ->triggeredByEvent(TrialEndingSoon::class)
            ->step('event-aware', EventAwareStep::class);
    }
}
