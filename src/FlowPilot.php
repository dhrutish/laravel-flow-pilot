<?php

namespace FlowPilot\LaravelFlowPilot;

use FlowPilot\LaravelFlowPilot\Jobs\RunFlowJob;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Registry\FlowRegistry;
use FlowPilot\LaravelFlowPilot\Runners\FlowRunner;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

class FlowPilot
{
    /**
     * @var array<class-string, true>
     */
    private array $registeredEventTriggers = [];

    public function __construct(
        private readonly FlowRunner $flowRunner,
        private readonly FlowRegistry $flowRegistry,
        private readonly Dispatcher $events,
        private readonly BusDispatcher $bus,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function run(string $flow, array $payload = []): FlowRun
    {
        return $this->flowRunner->run($flow, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $flow, array $payload = []): mixed
    {
        return $this->bus->dispatch(new RunFlowJob($flow, $payload));
    }

    public function registerEventTriggers(): void
    {
        foreach ($this->flowRegistry->all() as $flow) {
            $eventClass = $flow->eventTrigger();

            if ($eventClass === null) {
                continue;
            }

            if (isset($this->registeredEventTriggers[$flow::class])) {
                continue;
            }

            $flowClass = $flow::class;

            $this->events->listen($eventClass, function (object $event) use ($eventClass, $flowClass): void {
                $this->flowRunner->run($flowClass, [
                    'event' => $event,
                ], [
                    'trigger_type' => 'event',
                    'trigger_name' => $eventClass,
                ]);
            });

            $this->registeredEventTriggers[$flowClass] = true;
        }
    }
}
