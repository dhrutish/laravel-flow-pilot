<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use Illuminate\Console\Command;

class CancelFlowCommand extends Command
{
    protected $signature = 'flow:cancel {runId : Flow run ID or UUID}';

    protected $description = 'Cancel a flow run';

    public function handle(): int
    {
        $flowRun = FlowRun::query()
            ->with('stepRuns')
            ->where('id', $this->argument('runId'))
            ->orWhere('uuid', $this->argument('runId'))
            ->first();

        if ($flowRun === null) {
            $this->error('Flow run not found.');

            return self::FAILURE;
        }

        $flowRun->update([
            'status' => FlowRunStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        $flowRun->stepRuns()
            ->whereIn('status', [
                FlowStepStatus::Pending->value,
                FlowStepStatus::Running->value,
                FlowStepStatus::Retrying->value,
            ])
            ->update([
                'status' => FlowStepStatus::Cancelled,
            ]);

        $this->info("Flow run [{$flowRun->uuid}] cancelled.");

        return self::SUCCESS;
    }
}
