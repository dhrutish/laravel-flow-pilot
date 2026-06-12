<?php

namespace FlowPilot\LaravelFlowPilot;

use FlowPilot\LaravelFlowPilot\Jobs\RunFlowJob;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Registry\FlowRegistry;
use FlowPilot\LaravelFlowPilot\Runners\FlowRunner;
use FlowPilot\LaravelFlowPilot\Testing\FlowPilotFake;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;

class FlowPilot
{
    /**
     * @var array<class-string, true>
     */
    private array $registeredEventTriggers = [];

    private ?FlowPilotFake $fake = null;

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
        $run = $this->flowRunner->run($flow, $payload);

        $this->fake?->record($run);

        return $run;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(string $flow, array $payload = []): mixed
    {
        $job = new RunFlowJob($flow, $payload);

        $connection = config('flow-pilot.queue.connection');
        if ($connection !== null && $connection !== '') {
            $job->onConnection($connection);
        }

        $queue = config('flow-pilot.queue.queue');
        if ($queue !== null && $queue !== '') {
            $job->onQueue($queue);
        }

        return $this->bus->dispatch($job);
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
                $run = $this->flowRunner->run($flowClass, [
                    'event' => $event,
                ], [
                    'trigger_type' => 'event',
                    'trigger_name' => $eventClass,
                ]);

                $this->fake?->record($run);
            });

            $this->registeredEventTriggers[$flowClass] = true;
        }
    }

    public function fake(): FlowPilotFake
    {
        return $this->fake = new FlowPilotFake;
    }

    public function assertStarted(string $flowName): void
    {
        $this->activeFake()->assertStarted($flowName);
    }

    public function assertCompleted(string $flowName): void
    {
        $this->activeFake()->assertCompleted($flowName);
    }

    public function assertFailed(string $flowName): void
    {
        $this->activeFake()->assertFailed($flowName);
    }

    public function assertStepRan(string $flowName, string $stepClass): void
    {
        $this->activeFake()->assertStepRan($flowName, $stepClass);
    }

    public function assertStepSkipped(string $flowName, string $stepClass): void
    {
        $this->activeFake()->assertStepSkipped($flowName, $stepClass);
    }

    private function activeFake(): FlowPilotFake
    {
        return $this->fake ??= new FlowPilotFake;
    }
}
