<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeEventSourceDomainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:domain {name : The Aggregate Root Name (e.g. Article)} {table : The primary projection table (e.g. articles)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffolds God-Level Event Sourced Domain (Aggregate, Events, Projector, Backfill Command) to accelerate legacy migration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $table = strtolower($this->argument('table'));
        $lowerName = strtolower($name);
        
        $this->info("Initializing God-Level Domain Scaffolding for [{$name}] targeting table [{$table}]...");

        $this->generateEvents($name);
        $this->generateAggregate($name);
        $this->generateProjector($name, $table);
        $this->generateBackfill($name, $table);

        $this->info("Domain [{$name}] successfully scaffolded! You just saved 70% development time.");
        return Command::SUCCESS;
    }

    protected function generateEvents(string $name)
    {
        $path = app_path("Events");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        foreach (['Created', 'Updated'] as $action) {
            $eventName = "{$name}{$action}";
            $content = "<?php\n\nnamespace App\Events;\n\nclass {$eventName}\n{\n    public string \$uuid;\n    public array \$payload;\n    public string \$eventTime;\n\n    public function __construct(string \$uuid, array \$payload, string \$eventTime)\n    {\n        \$this->uuid = \$uuid;\n        \$this->payload = \$payload;\n        \$this->eventTime = \$eventTime;\n    }\n}\n";
            File::put("{$path}/{$eventName}.php", $content);
            $this->line("Created Event: {$eventName}");
        }
    }

    protected function generateAggregate(string $name)
    {
        $path = app_path("Aggregates");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $content = "<?php\n\nnamespace App\Aggregates;\n\nuse App\Events\\{$name}Created;\nuse App\Events\\{$name}Updated;\n\nclass {$name}Aggregate extends AggregateRoot\n{\n    public function create(array \$data, string \$eventTime): self\n    {\n        \$this->recordThat(new {$name}Created(\$this->uuid(), \$data, \$eventTime));\n        return \$this;\n    }\n\n    public function update(array \$data, string \$eventTime): self\n    {\n        \$this->recordThat(new {$name}Updated(\$this->uuid(), \$data, \$eventTime));\n        return \$this;\n    }\n}\n";
        File::put("{$path}/{$name}Aggregate.php", $content);
        $this->line("Created Aggregate: {$name}Aggregate");
    }

    protected function generateProjector(string $name, string $table)
    {
        $path = app_path("Services/Projectors");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $content = "<?php\n\nnamespace App\Services\Projectors;\n\nuse App\Services\Projector;\nuse App\Models\ProjectionVersion;\nuse Illuminate\Support\Facades\DB;\nuse App\Models\DomainOutbox;\n\nclass {$name}Projector extends Projector\n{\n    protected string \$table = '{$table}';\n    protected string \$idField = 'id';\n\n    protected function getVersionFromDatabase(): int\n    {\n        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;\n    }\n\n    protected function setVersion(int \$version): void\n    {\n        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => \$version]);\n    }\n\n    protected function getState(int \$aggregateId): array { return []; }\n    protected function restoreState(int \$aggregateId, array \$state): void {}\n    protected function setLastProcessedEventId(int \$aggregateId, int \$lastEventId): void {}\n\n    public function handle{$name}Created(array \$payload, DomainOutbox \$event): void\n    {\n        // Implement Idempotent UPSERT logic for {$table}\n    }\n\n    public function handle{$name}Updated(array \$payload, DomainOutbox \$event): void\n    {\n        // Implement Idempotent UPSERT logic for {$table}\n    }\n}\n";
        File::put("{$path}/{$name}Projector.php", $content);
        $this->line("Created Projector: {$name}Projector");
    }

    protected function generateBackfill(string $name, string $table)
    {
        $path = app_path("Console/Commands");
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $commandClassName = "Backfill{$name}sCommand";
        $signature = strtolower($name) . "s";
        
        $content = "<?php\n\nnamespace App\Console\Commands;\n\nuse Illuminate\Console\Command;\nuse Illuminate\Support\Facades\DB;\nuse App\Aggregates\\{$name}Aggregate;\nuse Illuminate\Support\Str;\n\nclass {$commandClassName} extends Command\n{\n    protected \$signature = 'backfill:{$signature} {--chunk=500}';\n    protected \$description = 'Extracts legacy {$table} records into the {$name}Aggregate Event pipeline.';\n\n    public function handle()\n    {\n        \$chunkSize = \$this->option('chunk');\n        \$this->info(\"Backfilling {$table}...\");\n\n        DB::table('{$table}')->orderBy('id')->chunk(\$chunkSize, function (\$records) {\n            foreach (\$records as \$record) {\n                \$aggregateUuid = Str::uuid()->toString();\n                {$name}Aggregate::retrieve(\$aggregateUuid)\n                    ->create((array)\$record)\n                    ->persist();\n            }\n        });\n\n        \$this->info(\"Finished. Trigger projector rebuilds.\");\n        return Command::SUCCESS;\n    }\n}\n";
        File::put("{$path}/{$commandClassName}.php", $content);
        $this->line("Created Backfill Command: {$commandClassName}");
    }
}
