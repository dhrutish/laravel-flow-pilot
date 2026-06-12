<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use FlowPilot\LaravelFlowPilot\Flow;
use Illuminate\Console\Command;
use InvalidArgumentException;

class ListFlowsCommand extends Command
{
    protected $signature = 'flow:list';

    protected $description = 'List registered flows';

    public function handle(): int
    {
        $flows = config('flow-pilot.flows', []);

        if ($flows === []) {
            $this->warn('No flows registered in config/flow-pilot.php.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($flows as $flowClass) {
            if (! is_subclass_of($flowClass, Flow::class)) {
                throw new InvalidArgumentException("Flow class [{$flowClass}] must extend Flow.");
            }

            /** @var Flow $flow */
            $flow = app($flowClass);
            $flow->prepare();

            $rows[] = [
                $flow->name(),
                $flowClass,
                count($flow->steps()),
                $flow->eventTrigger() ?? '-',
                $flow->isScheduled() ? 'yes' : 'no',
            ];
        }

        $this->table(['Name', 'Class', 'Steps', 'Event', 'Scheduled'], $rows);

        return self::SUCCESS;
    }
}
