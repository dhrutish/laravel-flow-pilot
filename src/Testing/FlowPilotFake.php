<?php

namespace FlowPilot\LaravelFlowPilot\Testing;

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use PHPUnit\Framework\Assert;

class FlowPilotFake
{
    /**
     * @var array<int, FlowRun>
     */
    private array $runs = [];

    public function record(FlowRun $run): void
    {
        $this->runs[] = $run->fresh(['stepRuns']);
    }

    public function assertStarted(string $flowName): void
    {
        Assert::assertNotNull(
            $this->findRun($flowName),
            "Flow [{$flowName}] was not started.",
        );
    }

    public function assertCompleted(string $flowName): void
    {
        $run = $this->findRun($flowName);

        Assert::assertNotNull($run, "Flow [{$flowName}] was not started.");
        Assert::assertSame(FlowRunStatus::Completed, $run->status);
    }

    public function assertFailed(string $flowName): void
    {
        $run = $this->findRun($flowName);

        Assert::assertNotNull($run, "Flow [{$flowName}] was not started.");
        Assert::assertSame(FlowRunStatus::Failed, $run->status);
    }

    public function assertStepRan(string $flowName, string $stepClass): void
    {
        $run = $this->findRun($flowName);

        Assert::assertNotNull($run, "Flow [{$flowName}] was not started.");
        Assert::assertTrue(
            $run->stepRuns->contains(fn ($stepRun): bool => $stepRun->class === $stepClass),
            "Step [{$stepClass}] did not run for flow [{$flowName}].",
        );
    }

    public function assertStepSkipped(string $flowName, string $stepClass): void
    {
        $run = $this->findRun($flowName);

        Assert::assertNotNull($run, "Flow [{$flowName}] was not started.");
        Assert::assertTrue(
            $run->stepRuns->contains(fn ($stepRun): bool => $stepRun->class === $stepClass && $stepRun->status === FlowStepStatus::Skipped),
            "Step [{$stepClass}] was not skipped for flow [{$flowName}].",
        );
    }

    private function findRun(string $flowName): ?FlowRun
    {
        foreach (array_reverse($this->runs) as $run) {
            if ($run->flow_name === $flowName || $run->flow_class === $flowName) {
                return $run;
            }
        }

        return null;
    }
}
