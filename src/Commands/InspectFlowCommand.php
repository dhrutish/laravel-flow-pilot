<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use Illuminate\Console\Command;

class InspectFlowCommand extends Command
{
    protected $signature = 'flow:inspect {runId : The flow run ID or UUID}';

    protected $description = 'Inspect a flow run and its steps';

    public function handle(): int
    {
        $runId = $this->argument('runId');

        $flowRun = FlowRun::query()
            ->with('stepRuns')
            ->where(function ($query) use ($runId) {
                $query->where('id', $runId)
                    ->orWhere('uuid', $runId);
            })
            ->first();

        if ($flowRun === null) {
            $this->error("Flow run [{$runId}] not found.");

            return self::FAILURE;
        }

        $this->info("Flow Run: {$flowRun->uuid}");
        $this->line("Name: {$flowRun->flow_name}");
        $this->line("Class: {$flowRun->flow_class}");
        $this->line("Status: {$flowRun->status->value}");
        $this->line('Payload: '.json_encode($flowRun->payload));
        $this->line("Started: {$flowRun->started_at}");
        $this->line("Completed: {$flowRun->completed_at}");
        $this->line("Failed: {$flowRun->failed_at}");

        if ($flowRun->failure_message) {
            $this->error("Failure: {$flowRun->failure_message}");
        }

        $this->newLine();
        $this->info('Steps:');

        $this->table(
            ['Position', 'Name', 'Status', 'Class'],
            $flowRun->stepRuns->map(fn ($step) => [
                $step->position,
                $step->name,
                $step->status->value,
                $step->class,
            ])->all()
        );

        return self::SUCCESS;
    }
}
