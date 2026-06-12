<?php

namespace FlowPilot\LaravelFlowPilot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFlowCommand extends Command
{
    protected $signature = 'make:flow {name : Flow class name} {--event= : Event class that triggers the flow} {--scheduled : Mark the flow as scheduled}';

    protected $description = 'Create a Flow Pilot flow class';

    public function handle(): int
    {
        $className = Str::finish(class_basename($this->argument('name')), 'Flow');
        $path = app_path("Flows/{$className}.php");

        if (File::exists($path)) {
            $this->error("Flow [{$className}] already exists.");

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        $definitionLines = [];

        if ($event = $this->option('event')) {
            $eventClass = '\\'.ltrim((string) $event, '\\');
            $definitionLines[] = "            ->triggeredByEvent({$eventClass}::class)";
        }

        if ($this->option('scheduled')) {
            $definitionLines[] = '            ->scheduled()';
        }

        $definitionLines[] = "            ->step('example-step', Steps\\ExampleStep::class);";

        $definition = implode("\n", $definitionLines);
        $flowName = Str::kebab(Str::beforeLast($className, 'Flow'));

        File::put($path, <<<PHP
<?php

namespace App\Flows;

use FlowPilot\LaravelFlowPilot\Flow;

class {$className} extends Flow
{
    public function name(): string
    {
        return '{$flowName}';
    }

    public function define(): void
    {
        \$this
{$definition}
    }
}
PHP);

        $this->info("Flow created: {$path}");

        return self::SUCCESS;
    }
}
