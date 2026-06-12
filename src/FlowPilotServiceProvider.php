<?php

namespace FlowPilot\LaravelFlowPilot;

use FlowPilot\LaravelFlowPilot\Commands\InspectFlowCommand;
use FlowPilot\LaravelFlowPilot\Commands\ListFlowsCommand;
use FlowPilot\LaravelFlowPilot\Commands\RunFlowCommand;
use FlowPilot\LaravelFlowPilot\Registry\FlowRegistry;
use FlowPilot\LaravelFlowPilot\Runners\FlowRunner;
use FlowPilot\LaravelFlowPilot\Runners\StepRunner;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\ServiceProvider;

class FlowPilotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flow-pilot.php', 'flow-pilot');

        $this->app->singleton(StepRunner::class);
        $this->app->singleton(FlowRegistry::class);
        $this->app->singleton(FlowRunner::class);
        $this->app->singleton('flow-pilot', function ($app) {
            return new FlowPilot(
                $app->make(FlowRunner::class),
                $app->make(FlowRegistry::class),
                $app->make('events'),
                $app->make(Dispatcher::class),
            );
        });
        $this->app->alias('flow-pilot', FlowPilot::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/flow-pilot.php' => config_path('flow-pilot.php'),
            ], 'flow-pilot-config');

            $timestamp = date('Y_m_d_His');

            $this->publishes([
                __DIR__.'/../database/migrations/create_flow_pilot_runs_table.php' => database_path('migrations/'.$timestamp.'_create_flow_pilot_runs_table.php'),
                __DIR__.'/../database/migrations/create_flow_pilot_steps_table.php' => database_path('migrations/'.$timestamp.'_create_flow_pilot_steps_table.php'),
            ], 'flow-pilot-migrations');

            $this->commands([
                RunFlowCommand::class,
                ListFlowsCommand::class,
                InspectFlowCommand::class,
            ]);
        }

        $this->app->make(FlowPilot::class)->registerEventTriggers();
    }
}
