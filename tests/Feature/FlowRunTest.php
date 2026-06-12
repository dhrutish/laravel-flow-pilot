<?php

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Facades\FlowPilot;
use FlowPilot\LaravelFlowPilot\Jobs\RunFlowJob;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\EventTriggeredFlow;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\TestFlow;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\TrialEndingSoon;
use Illuminate\Support\Facades\Queue;

it('runs a flow manually and persists run and step records', function () {
    $flowRun = FlowPilot::run(TestFlow::class, ['name' => 'Flow Pilot']);

    expect($flowRun)->toBeInstanceOf(FlowRun::class)
        ->and($flowRun->status)->toBe(FlowRunStatus::Completed)
        ->and($flowRun->flow_name)->toBe('test-flow')
        ->and($flowRun->payload)->toBe(['name' => 'Flow Pilot']);

    $this->assertDatabaseHas('flow_pilot_runs', [
        'id' => $flowRun->id,
        'flow_name' => 'test-flow',
        'status' => FlowRunStatus::Completed->value,
    ]);

    $stepRuns = FlowStepRun::query()->where('flow_run_id', $flowRun->id)->orderBy('position')->get();

    expect($stepRuns)->toHaveCount(2)
        ->and($stepRuns[0]->name)->toBe('first')
        ->and($stepRuns[0]->status)->toBe(FlowStepStatus::Completed)
        ->and($stepRuns[0]->output)->toBe(['greeting' => 'Hello, Flow Pilot'])
        ->and($stepRuns[1]->name)->toBe('second')
        ->and($stepRuns[1]->status)->toBe(FlowStepStatus::Completed)
        ->and($stepRuns[1]->output)->toBe(['message' => 'Hello, Flow Pilot from step two.']);
});

it('runs a flow via the artisan command', function () {
    config()->set('flow-pilot.flows', [TestFlow::class]);

    $this->artisan('flow:run', [
        'flow' => TestFlow::class,
        '--payload' => json_encode(['name' => 'Artisan']),
    ])
        ->expectsOutputToContain('finished with status [completed]')
        ->assertSuccessful();

    $flowRun = FlowRun::query()->first();

    expect($flowRun)->not->toBeNull()
        ->and($flowRun->status)->toBe(FlowRunStatus::Completed);
});

it('runs a registered flow by name', function () {
    config()->set('flow-pilot.flows', [TestFlow::class]);

    $flowRun = FlowPilot::run('test-flow', ['name' => 'Named']);

    expect($flowRun->status)->toBe(FlowRunStatus::Completed)
        ->and($flowRun->flow_class)->toBe(TestFlow::class)
        ->and($flowRun->stepRuns)->toHaveCount(2);
});

it('lists registered flows via artisan', function () {
    config()->set('flow-pilot.flows', [TestFlow::class]);

    $this->artisan('flow:list')
        ->expectsOutputToContain('test-flow')
        ->assertSuccessful();
});

it('runs a flow when its configured event is dispatched', function () {
    config()->set('flow-pilot.flows', [EventTriggeredFlow::class]);

    FlowPilot::registerEventTriggers();
    FlowPilot::registerEventTriggers();

    event(new TrialEndingSoon('Taylor'));

    $flowRun = FlowRun::query()->with('stepRuns')->first();

    expect($flowRun)->not->toBeNull()
        ->and($flowRun->status)->toBe(FlowRunStatus::Completed)
        ->and($flowRun->trigger_type)->toBe('event')
        ->and($flowRun->trigger_name)->toBe(TrialEndingSoon::class)
        ->and($flowRun->payload)->toBe(['event' => ['class' => TrialEndingSoon::class]])
        ->and($flowRun->stepRuns)->toHaveCount(1)
        ->and($flowRun->stepRuns->first()->output)->toBe(['name' => 'Taylor']);

    expect(FlowRun::query()->count())->toBe(1);
});

it('dispatches a flow onto the queue', function () {
    Queue::fake();

    FlowPilot::dispatch(TestFlow::class, ['name' => 'Queued']);

    Queue::assertPushed(RunFlowJob::class, function (RunFlowJob $job): bool {
        return $job->flow === TestFlow::class
            && $job->payload === ['name' => 'Queued'];
    });
});

it('inspects a flow run via artisan', function () {
    $flowRun = FlowPilot::run(TestFlow::class);

    $this->artisan('flow:inspect', ['runId' => $flowRun->uuid])
        ->expectsOutputToContain($flowRun->uuid)
        ->expectsOutputToContain('first')
        ->expectsOutputToContain('second')
        ->assertSuccessful();
});
