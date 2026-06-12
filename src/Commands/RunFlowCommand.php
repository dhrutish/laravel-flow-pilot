<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Facades\FlowPilot;
use Illuminate\Console\Command;

class RunFlowCommand extends Command
{
    protected $signature = 'flow:run
                            {flow : The registered flow name or fully qualified flow class name}
                            {--payload= : JSON payload for the flow}';

    protected $description = 'Run a flow synchronously';

    public function handle(): int
    {
        $flow = $this->argument('flow');
        $payload = [];

        if ($payloadOption = $this->option('payload')) {
            $payload = json_decode($payloadOption, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON payload provided.');

                return self::FAILURE;
            }
        }

        $flowRun = FlowPilot::run($flow, $payload);

        $this->info("Flow run [{$flowRun->uuid}] finished with status [{$flowRun->status->value}].");

        if ($flowRun->failure_message) {
            $this->error($flowRun->failure_message);
        }

        return $flowRun->status->value === 'completed'
            ? self::SUCCESS
            : self::FAILURE;
    }
}
