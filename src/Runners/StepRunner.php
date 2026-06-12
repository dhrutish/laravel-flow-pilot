<?php

namespace FlowPilot\LaravelFlowPilot\Runners;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Throwable;

class StepRunner
{
    public function __construct(
        private readonly Container $container,
    ) {}

    public function run(
        FlowRun $flowRun,
        string $name,
        string $class,
        int $position,
        FlowContext $context,
    ): StepResult {
        $stepRun = FlowStepRun::query()->create([
            'flow_run_id' => $flowRun->id,
            'name' => $name,
            'class' => $class,
            'status' => FlowStepStatus::Running,
            'position' => $position,
            'input' => [
                'payload' => $this->prepareForStorage($context->payload),
                'step_outputs' => $this->prepareForStorage($context->stepOutputs),
            ],
            'started_at' => now(),
        ]);

        try {
            if (! is_subclass_of($class, FlowStepContract::class)) {
                throw new InvalidArgumentException("Step class [{$class}] must implement FlowStepContract.");
            }

            /** @var FlowStepContract $step */
            $step = $this->container->make($class);
            $result = $step->handle($context);

            if ($result->success) {
                $stepRun->update([
                    'status' => FlowStepStatus::Completed,
                    'output' => $this->prepareForStorage($result->output),
                    'completed_at' => now(),
                ]);
            } else {
                $stepRun->update([
                    'status' => FlowStepStatus::Failed,
                    'output' => $this->prepareForStorage($result->output),
                    'failed_at' => now(),
                    'failure_message' => $result->failureMessage,
                ]);
            }

            return $result;
        } catch (Throwable $exception) {
            $stepRun->update([
                'status' => FlowStepStatus::Failed,
                'failed_at' => now(),
                'failure_message' => $exception->getMessage(),
            ]);

            return StepResult::failure($exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function prepareForStorage(array $payload): array
    {
        return array_map(fn (mixed $value): mixed => $this->normalizeValue($value), $payload);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        if (is_object($value)) {
            return ['class' => $value::class];
        }

        return $value;
    }
}
