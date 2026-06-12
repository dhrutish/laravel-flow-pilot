<?php

namespace FlowPilot\LaravelFlowPilot\Facades;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Testing\FlowPilotFake;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void assertCompleted(string $flowName)
 * @method static void assertFailed(string $flowName)
 * @method static void assertStarted(string $flowName)
 * @method static void assertStepRan(string $flowName, string $stepClass)
 * @method static void assertStepSkipped(string $flowName, string $stepClass)
 * @method static mixed dispatch(string $flow, array $payload = [])
 * @method static FlowPilotFake fake()
 * @method static void registerEventTriggers()
 * @method static FlowRun run(string $flow, array $payload = [])
 *
 * @see \FlowPilot\LaravelFlowPilot\FlowPilot
 */
class FlowPilot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'flow-pilot';
    }
}
