<?php

namespace FlowPilot\LaravelFlowPilot\Tests\Stubs;

use FlowPilot\LaravelFlowPilot\Flow;

class WeeklyReportFlow extends Flow
{
    public function name(): string
    {
        return 'weekly-report';
    }

    public function define(): void
    {
        $this
            ->step('prepare-report', FirstStep::class)
            ->step('email-report', SecondStep::class);
    }
}
