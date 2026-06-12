<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Registry\FlowRegistry;
use Illuminate\Console\Command;

class ListScheduledFlowsCommand extends Command
{
    protected $signature = 'flow:schedule:list';

    protected $description = 'List scheduled Flow Pilot flows';

    public function handle(FlowRegistry $registry): int
    {
        $rows = [];

        foreach ($registry->all() as $flow) {
            if (! $flow->isScheduled()) {
                continue;
            }

            $rows[] = [
                $flow->name(),
                $flow::class,
                count($flow->steps()),
            ];
        }

        if ($rows === []) {
            $this->warn('No scheduled flows registered.');

            return self::SUCCESS;
        }

        $this->table(['Name', 'Class', 'Steps'], $rows);

        return self::SUCCESS;
    }
}
