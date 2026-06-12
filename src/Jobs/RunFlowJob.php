<?php

namespace FlowPilot\LaravelFlowPilot\Jobs;

use FlowPilot\LaravelFlowPilot\Runners\FlowRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunFlowJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $flow,
        public readonly array $payload = [],
    ) {}

    public function tries(): int
    {
        return (int) config('flow-pilot.retries.attempts', 3);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return config('flow-pilot.retries.backoff', [60, 300, 900]);
    }

    public function handle(FlowRunner $runner): void
    {
        $runner->run($this->flow, $this->payload);
    }
}
