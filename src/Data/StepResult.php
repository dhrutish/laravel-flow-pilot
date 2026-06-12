<?php

namespace FlowPilot\LaravelFlowPilot\Data;

class StepResult
{
    /**
     * @param  array<string, mixed>  $output
     */
    public function __construct(
        public readonly bool $success,
        public readonly array $output = [],
        public readonly ?string $failureMessage = null,
    ) {}

    /**
     * @param  array<string, mixed>  $output
     */
    public static function success(array $output = []): self
    {
        return new self(true, $output);
    }

    /**
     * @param  array<string, mixed>  $output
     */
    public static function failure(string $message, array $output = []): self
    {
        return new self(false, $output, $message);
    }
}
