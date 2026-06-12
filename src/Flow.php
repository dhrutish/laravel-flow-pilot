<?php

namespace FlowPilot\LaravelFlowPilot;

abstract class Flow
{
    /**
     * @var array<int, array{name: string, class: class-string}>
     */
    protected array $steps = [];

    /**
     * @var class-string|null
     */
    protected ?string $eventTrigger = null;

    abstract public function name(): string;

    abstract public function define(): void;

    public function prepare(): static
    {
        $this->steps = [];
        $this->eventTrigger = null;

        $this->define();

        return $this;
    }

    /**
     * @param  class-string  $nameOrClass
     * @param  class-string|null  $class
     */
    public function step(string $nameOrClass, ?string $class = null): static
    {
        $stepClass = $class ?? $nameOrClass;
        $name = $class === null ? class_basename($stepClass) : $nameOrClass;

        $this->steps[] = [
            'name' => $name,
            'class' => $stepClass,
        ];

        return $this;
    }

    /**
     * @param  class-string  $eventClass
     */
    public function triggeredByEvent(string $eventClass): static
    {
        $this->eventTrigger = $eventClass;

        return $this;
    }

    /**
     * @return class-string|null
     */
    public function eventTrigger(): ?string
    {
        return $this->eventTrigger;
    }

    /**
     * @return array<int, array{name: string, class: class-string}>
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
