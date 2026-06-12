<?php

namespace FlowPilot\LaravelFlowPilot\Data;

use FlowPilot\LaravelFlowPilot\Models\FlowRun;

class FlowContext
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $stepOutputs
     */
    public function __construct(
        public readonly array $payload,
        public readonly FlowRun $flowRun,
        public readonly array $stepOutputs = [],
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    public function payload(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    public function stepOutput(string $stepName, mixed $default = null): mixed
    {
        return $this->stepOutputs[$stepName] ?? $default;
    }
}
