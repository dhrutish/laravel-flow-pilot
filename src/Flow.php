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

    protected bool $scheduled = false;

    /**
     * @var array{attempts: int, backoff: array<int, int>}|null
     */
    protected ?array $retry = null;

    abstract public function name(): string;

    abstract public function define(): void;

    public function prepare(): static
    {
        $this->steps = [];
        $this->eventTrigger = null;
        $this->scheduled = false;
        $this->retry = null;

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

    public function scheduled(?callable $callback = null): static
    {
        $this->scheduled = true;

        return $this;
    }

    public function isScheduled(): bool
    {
        return $this->scheduled;
    }

    /**
     * @param  array<int, int>  $backoff
     */
    public function retry(int $attempts = 3, array $backoff = [60, 300, 900]): static
    {
        $this->retry = [
            'attempts' => $attempts,
            'backoff' => $backoff,
        ];

        return $this;
    }

    /**
     * @return array{attempts: int, backoff: array<int, int>}
     */
    public function retryOptions(): array
    {
        return $this->retry ?? [
            'attempts' => (int) config('flow-pilot.retries.attempts', 3),
            'backoff' => config('flow-pilot.retries.backoff', [60, 300, 900]),
        ];
    }

    /**
     * @return array<int, array{name: string, class: class-string}>
     */
    public function steps(): array
    {
        return $this->steps;
    }
}
