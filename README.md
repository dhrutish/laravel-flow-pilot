# Laravel Flow Pilot

Laravel Flow Pilot is a Laravel-native workflow automation package for defining, running, tracking, retrying, and debugging multi-step business flows.

## Installation

```bash
composer require flow-pilot/laravel-flow-pilot
php artisan flow-pilot:install
php artisan migrate
```

Register flows in `config/flow-pilot.php`:

```php
'flows' => [
    App\Flows\TrialEndingReminderFlow::class,
],
```

## Define A Flow

```php
use FlowPilot\LaravelFlowPilot\Flow;

class TrialEndingReminderFlow extends Flow
{
    public function name(): string
    {
        return 'trial-ending-reminder';
    }

    public function define(): void
    {
        $this
            ->triggeredByEvent(TrialEndingSoon::class)
            ->step('send-reminder', SendTrialReminderEmail::class)
            ->retry(attempts: 3, backoff: [60, 300, 900]);
    }
}
```

## Define A Step

```php
use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class SendTrialReminderEmail implements FlowStepContract
{
    public function handle(FlowContext $context): StepResult
    {
        return StepResult::success(['sent' => true]);
    }
}
```

## Run Flows

```php
FlowPilot::run('trial-ending-reminder', ['user_id' => 123]);
FlowPilot::dispatch('trial-ending-reminder', ['user_id' => 123]);
```

Queued flows use Laravel's queue system. Configure the queue connection and queue name in `config/flow-pilot.php` or with environment variables:

```dotenv
FLOW_PILOT_QUEUE_CONNECTION=redis
FLOW_PILOT_QUEUE=critical-flows
```

When the queue connection is unset, Flow Pilot leaves the job connection unset so Laravel can use the application's default queue connection. The published config defaults `queue` to `default`; set `queue` to `null` in `config/flow-pilot.php` if you want Laravel to choose the queue name entirely from the application defaults.

## Artisan

```bash
php artisan make:flow TrialEndingReminderFlow
php artisan make:flow-step SendTrialReminderEmail
php artisan flow:list
php artisan flow:run trial-ending-reminder --payload='{"user_id":123}'
php artisan flow:run trial-ending-reminder --queued
php artisan flow:inspect <run-id-or-uuid>
php artisan flow:retry <run-id-or-uuid>
php artisan flow:cancel <run-id-or-uuid>
php artisan flow:prune
php artisan flow:schedule:list
```

## Testing

```php
FlowPilot::fake();

FlowPilot::run('trial-ending-reminder');

FlowPilot::assertStarted('trial-ending-reminder');
FlowPilot::assertCompleted('trial-ending-reminder');
FlowPilot::assertStepRan('trial-ending-reminder', SendTrialReminderEmail::class);
```

## Security

Payload keys configured in `flow-pilot.payloads.redact` are recursively replaced with `[REDACTED]` before storage.
