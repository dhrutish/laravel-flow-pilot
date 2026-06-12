<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Facades\FlowPilot;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use Illuminate\Console\Command;

class RetryFlowCommand extends Command
{
    protected $signature = 'flow:retry {runId : Flow run ID or UUID}';

    protected $description = 'Retry a flow run from the beginning';

    public function handle(): int
    {
        $flowRun = $this->findRun((string) $this->argument('runId'));

        if ($flowRun === null) {
            $this->error('Flow run not found.');

            return self::FAILURE;
        }

        $retry = FlowPilot::run($flowRun->flow_class, $flowRun->payload ?? []);

        $this->info("Retried flow run [{$flowRun->uuid}] as [{$retry->uuid}].");

        return $retry->status->value === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    private function findRun(string $runId): ?FlowRun
    {
        return FlowRun::query()
            ->where('id', $runId)
            ->orWhere('uuid', $runId)
            ->first();
    }
}
