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

    public function handle(FlowRunner $runner): void
    {
        $runner->run($this->flow, $this->payload);
    }
}
