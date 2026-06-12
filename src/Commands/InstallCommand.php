<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'flow-pilot:install {--migrate : Run database migrations after publishing}';

    protected $description = 'Install Flow Pilot config, migrations, and application flow folders';

    public function handle(): int
    {
        $this->call('vendor:publish', [
            '--tag' => 'flow-pilot-config',
            '--force' => true,
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'flow-pilot-migrations',
            '--force' => true,
        ]);

        File::ensureDirectoryExists(app_path('Flows/Steps'));

        if ($this->option('migrate')) {
            $this->call('migrate');
        }

        $this->info('Flow Pilot installed. Add your flow classes to config/flow-pilot.php.');

        return self::SUCCESS;
    }
}
