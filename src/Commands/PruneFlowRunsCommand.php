<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use Illuminate\Console\Command;

class PruneFlowRunsCommand extends Command
{
    protected $signature = 'flow:prune {--completed-days=} {--failed-days=}';

    protected $description = 'Prune old Flow Pilot runs';

    public function handle(): int
    {
        $completedDays = (int) ($this->option('completed-days') ?: config('flow-pilot.prune.completed_after_days', 30));
        $failedDays = (int) ($this->option('failed-days') ?: config('flow-pilot.prune.failed_after_days', 90));

        $completed = FlowRun::query()
            ->where('status', FlowRunStatus::Completed->value)
            ->where('created_at', '<', now()->subDays($completedDays))
            ->delete();

        $failed = FlowRun::query()
            ->where('status', FlowRunStatus::Failed->value)
            ->where('created_at', '<', now()->subDays($failedDays))
            ->delete();

        $this->info("Pruned {$completed} completed and {$failed} failed flow runs.");

        return self::SUCCESS;
    }
}
