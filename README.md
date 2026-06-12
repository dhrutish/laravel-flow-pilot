# Laravel Flow Pilot

Laravel Flow Pilot is a Laravel-native workflow automation package for defining, running, tracking, retrying, and debugging multi-step business flows.

## Installation

After the package is published to Packagist, install it with Composer:

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

## Local Development Install

Use a Composer path repository when testing this package before a Packagist release.

From a workspace that contains both a fresh Laravel 10 app and this package:

```bash
composer create-project laravel/laravel:^10.0 flow-pilot-demo
git clone https://github.com/dhrutish/laravel-flow-pilot.git
cd flow-pilot-demo
```

Add the local package path:

```bash
composer config repositories.flow-pilot path ../laravel-flow-pilot
composer require flow-pilot/laravel-flow-pilot:"*"
```

Publish the package assets and run the migrations:

```bash
php artisan flow-pilot:install
php artisan migrate
```

The install command publishes `config/flow-pilot.php` and the package migrations. If you prefer to publish manually, use:

```bash
php artisan vendor:publish --tag=flow-pilot-config
php artisan vendor:publish --tag=flow-pilot-migrations
php artisan migrate
```

## Fresh Laravel 10 Test Flow

Create a flow and a step in the demo app:

```bash
php artisan make:flow TrialEndingReminderFlow
php artisan make:flow-step SendTrialReminderEmail
```

Register the flow in `config/flow-pilot.php`:

```php
'flows' => [
    App\Flows\TrialEndingReminderFlow::class,
],
```

Edit `app/Flows/TrialEndingReminderFlow.php` so the generated flow uses the step you created:

```php
use App\Flows\Steps\SendTrialReminderEmail;

public function define(): void
{
    $this->step('send-trial-reminder-email', SendTrialReminderEmail::class);
}
```

Edit `app/Flows/Steps/SendTrialReminderEmail.php` so the step returns a successful result:

```php
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

public function handle(FlowContext $context): StepResult
{
    return StepResult::success([
        'user_id' => $context->get('user_id'),
        'sent' => true,
    ]);
}
```

Run the flow from Artisan:

```bash
php artisan flow:run trial-ending-reminder --payload='{"user_id":123}'
php artisan flow:list
```

For queued execution, set up a Laravel queue connection and run a worker:

```bash
php artisan flow:run trial-ending-reminder --payload='{"user_id":123}' --queued
php artisan queue:work
```

## Package Test Suite

Run the package tests from the package checkout:

```bash
cd ../laravel-flow-pilot
composer install
composer test
```

The test suite uses Orchestra Testbench with an in-memory SQLite database.

## Current MVP Limitations

- The package targets Laravel 10 and PHP 8.1+.
- Flow definitions are registered through `config/flow-pilot.php`; automatic discovery is not included yet.
- Queued flows use the host application's configured Laravel queue connection and queue name.
- The package stores flow run and step payloads in database JSON columns, with sensitive keys redacted according to `flow-pilot.payloads.redact`.
- The package is pre-Packagist in local development, so contributors should use the path repository setup above.

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
