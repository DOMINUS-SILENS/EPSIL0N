<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SafeMigrate extends Command
{
    protected $signature = 'migrate:safe {--force}';

    protected $description = 'Run migrations with strict intent-based safety checks';

    public function handle()
    {
        $files = File::files(database_path('migrations'));

        $violations = [];
        $warnings = [];

        foreach ($files as $file) {
            $content = File::get($file);
            $name = $file->getFilename();

            if ($this->hasDestructiveSchema($content)) {
                $violations[$name][] = 'destructive schema operation';
            }

            $statements = $this->extractDbStatements($content);

            foreach ($statements as $sql) {
                $sql = $this->normalizeSql($sql);

                if ($this->isSafeViewSql($sql)) {
                    $warnings[$name][] = 'controlled SQL (view/index creation)';

                    continue;
                }

                if ($this->isDestructiveSql($sql)) {
                    $violations[$name][] = 'destructive SQL statement';
                }
            }
        }

        if (! empty($violations)) {
            $this->error('Destructive migration detected:');

            foreach ($violations as $file => $issues) {
                $this->line(" - {$file}: ".implode(', ', array_unique($issues)));
            }

            if (! $this->option('force')) {
                $this->error('Aborted. Use --force to override.');

                return Command::FAILURE;
            }
        }

        if (! empty($warnings)) {
            $this->warn('Controlled-risk operations detected:');

            foreach ($warnings as $file => $issues) {
                $this->line(" - {$file}: ".implode(', ', array_unique($issues)));
            }
        }

        $this->info('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        // Run pretend to see actual SQL
        $this->info('Running migration pretend...');
        $this->call('migrate', ['--pretend' => true]);

        // Optionally ask for confirmation
        if (! $this->option('force') && ! $this->confirm('Proceed with migrations?')) {
            return Command::FAILURE;
        }

        $this->call('migrate', ['--force' => true]);

        return Command::SUCCESS;
    }

    private function hasDestructiveSchema(string $content): bool
    {
        return str_contains($content, 'dropColumn')
            || str_contains($content, 'renameColumn')
            || str_contains($content, 'dropIndex')
            || str_contains($content, 'dropForeign');
    }

    private function extractDbStatements(string $content): array
    {
        preg_match_all('/DB::statement\((.*?)\);/s', $content, $matches);

        $statements = $matches[1] ?? [];

        return array_map(function ($s) {
            return trim($s, " '\"\n\r\t");
        }, $statements);
    }

    private function normalizeSql(string $sql): string
    {
        return strtoupper(trim($sql, " '\"\n\r\t"));
    }

    private function isSafeViewSql(string $sql): bool
    {
        return str_contains($sql, 'CREATE VIEW')
            || str_contains($sql, 'CREATE OR REPLACE VIEW')
            || str_contains($sql, 'CREATE INDEX');
    }

    private function isDestructiveSql(string $sql): bool
    {
        return preg_match('/\bDROP\b/', $sql)
            || preg_match('/\bTRUNCATE\b/', $sql)
            || preg_match('/ALTER\s+.*\bDROP\b/', $sql);
    }
}
