<?php

use FlowPilot\LaravelFlowPilot\Enums\FlowRunStatus;
use FlowPilot\LaravelFlowPilot\Enums\FlowStepStatus;
use FlowPilot\LaravelFlowPilot\Events\FlowCompleted;
use FlowPilot\LaravelFlowPilot\Events\FlowFailed;
use FlowPilot\LaravelFlowPilot\Events\FlowStarted;
use FlowPilot\LaravelFlowPilot\Events\FlowStepCompleted;
use FlowPilot\LaravelFlowPilot\Events\FlowStepFailed;
use FlowPilot\LaravelFlowPilot\Events\FlowStepStarted;
use FlowPilot\LaravelFlowPilot\Facades\FlowPilot;
use FlowPilot\LaravelFlowPilot\Jobs\RunFlowJob;
use FlowPilot\LaravelFlowPilot\Models\FlowRun;
use FlowPilot\LaravelFlowPilot\Models\FlowStepRun;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\EventTriggeredFlow;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\FailingFlow;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\FirstStep;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\TestFlow;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\TrialEndingSoon;
use FlowPilot\LaravelFlowPilot\Tests\Stubs\WeeklyReportFlow;
use Illuminate\Support\Facades\Event;
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

it('redacts sensitive values before storing flow and step payloads', function () {
    $flowRun = FlowPilot::run(TestFlow::class, [
        'name' => 'Sensitive',
        'api_key' => 'secret-key',
        'nested' => [
            'token' => 'secret-token',
        ],
    ]);

    $stepRun = $flowRun->stepRuns->first();

    expect($flowRun->payload)->toMatchArray([
        'api_key' => '[REDACTED]',
        'nested' => [
            'token' => '[REDACTED]',
        ],
    ])
        ->and($stepRun->input['payload'])->toMatchArray([
            'api_key' => '[REDACTED]',
            'nested' => [
                'token' => '[REDACTED]',
            ],
        ]);
});

it('marks a flow as failed when a step returns failure', function () {
    $flowRun = FlowPilot::run(FailingFlow::class);
    $stepRun = $flowRun->stepRuns->first();

    expect($flowRun->status)->toBe(FlowRunStatus::Failed)
        ->and($flowRun->failure_message)->toBe('The step failed.')
        ->and($stepRun->status)->toBe(FlowStepStatus::Failed)
        ->and($stepRun->failure_message)->toBe('The step failed.')
        ->and($stepRun->output)->toBe(['api_key' => '[REDACTED]']);
});

it('dispatches lifecycle events for successful and failed flows', function () {
    Event::fake([
        FlowStarted::class,
        FlowCompleted::class,
        FlowFailed::class,
        FlowStepStarted::class,
        FlowStepCompleted::class,
        FlowStepFailed::class,
    ]);

    FlowPilot::run(TestFlow::class);
    FlowPilot::run(FailingFlow::class);

    Event::assertDispatched(FlowStarted::class, 2);
    Event::assertDispatched(FlowCompleted::class, 1);
    Event::assertDispatched(FlowFailed::class, 1);
    Event::assertDispatched(FlowStepStarted::class, 3);
    Event::assertDispatched(FlowStepCompleted::class, 2);
    Event::assertDispatched(FlowStepFailed::class, 1);
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

it('dispatches a flow via the artisan command', function () {
    Queue::fake();

    $this->artisan('flow:run', [
        'flow' => TestFlow::class,
        '--payload' => json_encode(['name' => 'Queued command']),
        '--queued' => true,
    ])
        ->expectsOutputToContain('dispatched to the queue')
        ->assertSuccessful();

    Queue::assertPushed(RunFlowJob::class);
});

it('runs a registered flow by name', function () {
    config()->set('flow-pilot.flows', [TestFlow::class, WeeklyReportFlow::class]);

    $flowRun = FlowPilot::run('test-flow', ['name' => 'Named']);
    $weeklyReportRun = FlowPilot::run('weekly-report', ['name' => 'Report']);

    expect($flowRun->status)->toBe(FlowRunStatus::Completed)
        ->and($flowRun->flow_class)->toBe(TestFlow::class)
        ->and($flowRun->stepRuns)->toHaveCount(2)
        ->and($weeklyReportRun->status)->toBe(FlowRunStatus::Completed)
        ->and($weeklyReportRun->flow_class)->toBe(WeeklyReportFlow::class);
});

it('lists scheduled flows via artisan', function () {
    config()->set('flow-pilot.flows', [TestFlow::class, WeeklyReportFlow::class]);

    $this->artisan('flow:schedule:list')
        ->expectsOutputToContain('weekly-report')
        ->assertSuccessful();
});

it('retries a failed flow via artisan', function () {
    $failedRun = FlowPilot::run(FailingFlow::class);

    $this->artisan('flow:retry', ['runId' => $failedRun->uuid])
        ->expectsOutputToContain('Retried flow run')
        ->assertFailed();

    expect(FlowRun::query()->count())->toBe(2);
});

it('cancels a flow via artisan', function () {
    $flowRun = FlowPilot::run(TestFlow::class);

    $this->artisan('flow:cancel', ['runId' => $flowRun->uuid])
        ->expectsOutputToContain('cancelled')
        ->assertSuccessful();

    expect($flowRun->fresh()->status)->toBe(FlowRunStatus::Cancelled)
        ->and($flowRun->fresh()->cancelled_at)->not->toBeNull();
});

it('prunes old completed and failed runs', function () {
    $completedRun = FlowPilot::run(TestFlow::class);
    $failedRun = FlowPilot::run(FailingFlow::class);
    $recentRun = FlowPilot::run(TestFlow::class);

    $completedRun->forceFill(['created_at' => now()->subDays(45)])->save();
    $failedRun->forceFill(['created_at' => now()->subDays(120)])->save();

    $this->artisan('flow:prune', [
        '--completed-days' => 30,
        '--failed-days' => 90,
    ])
        ->expectsOutputToContain('Pruned')
        ->assertSuccessful();

    expect(FlowRun::query()->pluck('id')->all())->toBe([$recentRun->id]);
});

it('records fake flow assertions', function () {
    FlowPilot::fake();

    FlowPilot::run(TestFlow::class);
    FlowPilot::run(FailingFlow::class);

    FlowPilot::assertStarted('test-flow');
    FlowPilot::assertCompleted('test-flow');
    FlowPilot::assertFailed('failing-flow');
    FlowPilot::assertStepRan('test-flow', FirstStep::class);
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

it('applies configured queue connection and queue name when dispatching a flow', function () {
    Queue::fake();
    config()->set('flow-pilot.queue.connection', 'redis');
    config()->set('flow-pilot.queue.queue', 'critical-flows');

    FlowPilot::dispatch(TestFlow::class, ['name' => 'Queued']);

    Queue::assertPushed(RunFlowJob::class, function (RunFlowJob $job): bool {
        return $job->connection === 'redis'
            && $job->queue === 'critical-flows';
    });
});

it('dispatches flows when queue connection and queue name are not configured', function () {
    Queue::fake();
    config()->set('flow-pilot.queue.connection', null);
    config()->set('flow-pilot.queue.queue', null);

    FlowPilot::dispatch(TestFlow::class, ['name' => 'Queued']);

    Queue::assertPushed(RunFlowJob::class, function (RunFlowJob $job): bool {
        return $job->connection === null
            && $job->queue === null
            && $job->flow === TestFlow::class;
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
