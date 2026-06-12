<?php

namespace FlowPilot\LaravelFlowPilot\Facades;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed dispatch(string $flow, array $payload = [])
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
