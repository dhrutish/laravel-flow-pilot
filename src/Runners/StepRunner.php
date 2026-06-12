<?php

namespace FlowPilot\LaravelFlowPilot\Runners;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Events\FlowStepCompleted;
use FlowPilot\LaravelFlowPilot\Events\FlowStepFailed;
use FlowPilot\LaravelFlowPilot\Events\FlowStepStarted;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;
use FlowPilot\LaravelFlowPilot\Support\PayloadNormalizer;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Throwable;

class StepRunner
{
    public function __construct(
        private readonly Container $container,
        private readonly PayloadNormalizer $payloadNormalizer,
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
                'payload' => $this->payloadNormalizer->forStorage($context->payload),
                'step_outputs' => $this->payloadNormalizer->forStorage($context->stepOutputs),
            ],
            'started_at' => now(),
        ]);

        event(new FlowStepStarted($stepRun));

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
                    'output' => $this->payloadNormalizer->forStorage($result->output),
                    'completed_at' => now(),
                ]);

                event(new FlowStepCompleted($stepRun->fresh()));
            } else {
                $stepRun->update([
                    'status' => FlowStepStatus::Failed,
                    'output' => $this->payloadNormalizer->forStorage($result->output),
                    'failed_at' => now(),
                    'failure_message' => $result->failureMessage,
                ]);

                event(new FlowStepFailed($stepRun->fresh()));
            }

            return $result;
        } catch (Throwable $exception) {
            $stepRun->update([
                'status' => FlowStepStatus::Failed,
                'failed_at' => now(),
                'failure_message' => $exception->getMessage(),
            ]);

            event(new FlowStepFailed($stepRun->fresh()));

            return StepResult::failure($exception->getMessage());
        }
    }
}
