<?php

namespace FlowPilot\LaravelFlowPilot\Runners;

use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Events\FlowCompleted;
use FlowPilot\LaravelFlowPilot\Events\FlowFailed;
use FlowPilot\LaravelFlowPilot\Events\FlowStarted;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Registry\FlowRegistry;
use FlowPilot\LaravelFlowPilot\Support\PayloadNormalizer;
use Illuminate\Support\Str;
use Throwable;

class FlowRunner
{
    public function __construct(
        private readonly FlowRegistry $flowRegistry,
        private readonly StepRunner $stepRunner,
        private readonly PayloadNormalizer $payloadNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{trigger_type?: string|null, trigger_name?: string|null}  $options
     */
    public function run(string $flow, array $payload = [], array $options = []): FlowRun
    {
        $flowDefinition = $this->flowRegistry->resolve($flow);

        $flowRun = FlowRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'flow_name' => $flowDefinition->name(),
            'flow_class' => $flowDefinition::class,
            'status' => FlowRunStatus::Running,
            'trigger_type' => $options['trigger_type'] ?? null,
            'trigger_name' => $options['trigger_name'] ?? null,
            'payload' => $this->payloadNormalizer->forStorage($payload),
            'metadata' => [
                'retry' => $flowDefinition->retryOptions(),
            ],
            'started_at' => now(),
        ]);

        event(new FlowStarted($flowRun));

        $stepOutputs = [];
        $context = new FlowContext($payload, $flowRun, $stepOutputs);

        try {
            foreach ($flowDefinition->steps() as $position => $stepDefinition) {
                $result = $this->stepRunner->run(
                    $flowRun,
                    $stepDefinition['name'],
                    $stepDefinition['class'],
                    $position,
                    $context,
                );

                if (! $result->success) {
                    $flowRun->update([
                        'status' => FlowRunStatus::Failed,
                        'failed_at' => now(),
                        'failure_message' => $result->failureMessage,
                    ]);

                    event(new FlowFailed($flowRun->fresh(['stepRuns'])));

                    return $flowRun->fresh(['stepRuns']);
                }

                $stepOutputs[$stepDefinition['name']] = $result->output;
                $context = new FlowContext($payload, $flowRun, $stepOutputs);
            }

            $flowRun->update([
                'status' => FlowRunStatus::Completed,
                'completed_at' => now(),
            ]);

            event(new FlowCompleted($flowRun->fresh(['stepRuns'])));
        } catch (Throwable $exception) {
            $flowRun->update([
                'status' => FlowRunStatus::Failed,
                'failed_at' => now(),
                'failure_message' => $exception->getMessage(),
            ]);

            event(new FlowFailed($flowRun->fresh(['stepRuns'])));
        }

        return $flowRun->fresh(['stepRuns']);
    }
}
