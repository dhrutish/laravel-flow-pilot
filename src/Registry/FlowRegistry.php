<?php

namespace FlowPilot\LaravelFlowPilot\Registry;

use FlowPilot\LaravelFlowPilot\Flow;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class FlowRegistry
{
    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * @return array<int, Flow>
     */
    public function all(): array
    {
        return array_map(
            fn (string $flowClass): Flow => $this->make($flowClass),
            config('flow-pilot.flows', []),
        );
    }

    public function resolve(string $flow): Flow
    {
        if (is_subclass_of($flow, Flow::class)) {
            return $this->make($flow);
        }

        foreach ($this->all() as $registeredFlow) {
            if ($registeredFlow->name() === $flow) {
                return $registeredFlow;
            }
        }

        throw new InvalidArgumentException("Flow [{$flow}] is not registered.");
    }

    /**
     * @param  class-string<Flow>  $flowClass
     */
    private function make(string $flowClass): Flow
    {
        if (! is_subclass_of($flowClass, Flow::class)) {
            throw new InvalidArgumentException("Flow class [{$flowClass}] must extend Flow.");
        }

        /** @var Flow $flow */
        $flow = $this->container->make($flowClass);

        return $flow->prepare();
    }
}
