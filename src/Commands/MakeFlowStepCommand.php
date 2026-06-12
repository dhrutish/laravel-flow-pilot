<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFlowStepCommand extends Command
{
    protected $signature = 'make:flow-step {name : Step class name}';

    protected $description = 'Create a Flow Pilot step class';

    public function handle(): int
    {
        $className = class_basename($this->argument('name'));
        $path = app_path("Flows/Steps/{$className}.php");

        if (File::exists($path)) {
            $this->error("Flow step [{$className}] already exists.");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        $key = Str::snake(Str::replaceEnd('Step', '', $className));

        File::put($path, <<<PHP
<?php

namespace App\Flows\Steps;

use FlowPilot\LaravelFlowPilot\Contracts\FlowStepContract;
use FlowPilot\LaravelFlowPilot\Data\FlowContext;
use FlowPilot\LaravelFlowPilot\Data\StepResult;

class {$className} implements FlowStepContract
{
    public function handle(FlowContext \$context): StepResult
    {
        return StepResult::success([
            '{$key}' => true,
        ]);
    }
}
PHP);

        $this->info("Flow step created: {$path}");

        return self::SUCCESS;
    }
}
