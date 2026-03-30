<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DumpDatabase extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'db:dump {--type=auto : Format (auto, sqlite, mysql, sql)}
                                   {--compress : Compress output with gzip}
                                   {--include-data=true : Include actual data}';

    /**
     * The console command description.
     */
    protected $description = 'Generate database dumps for EPSILON ERP';

    public function handle()
    {
        $type = $this->option('type');
        $compress = $this->option('compress');
        $includeData = $this->option('include-data');

        // Auto-detect connection type
        if ($type === 'auto') {
            $type = config('database.default');
        }

        $this->line("🔄 Generating {$type} database dump...\n");

        try {
            if ($type === 'sqlite') {
                $this->dumpSQLite($includeData);
            } elseif ($type === 'mysql') {
                $this->dumpMySQL($includeData);
            } else {
                $this->error("Unknown database type: {$type}");
                return self::FAILURE;
            }

            if ($compress) {
                $this->info("\n✓ Database dump completed successfully");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function dumpSQLite($includeData)
    {
        $dbPath = database_path('database.sqlite');

        if (!file_exists($dbPath)) {
            throw new \Exception("SQLite database not found: $dbPath");
        }

        $dumpDir = database_path('dumps');
        if (!is_dir($dumpDir)) {
            mkdir($dumpDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "epsilon_sqlite_{$timestamp}.sql";
        $filepath = "{$dumpDir}/{$filename}";

        // Create dump using PHP PDO/SQLite
        $pdo = new \PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $dump = "-- EPSILON ERP SQLite Dump\n";
        $dump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $dump .= "-- Database: " . basename($dbPath) . "\n";
        $dump .= "-- ---\n\n";

        $dump .= "PRAGMA foreign_keys = ON;\n\n";

        // Get all tables
        $tables = $pdo->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        )->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $schema = $pdo->query(
                "SELECT sql FROM sqlite_master WHERE type='table' AND name = '{$table}'"
            )->fetch(\PDO::FETCH_COLUMN);

            $dump .= "{$schema};\n\n";

            // Include data if requested
            if ($includeData) {
                $rows = $pdo->query("SELECT * FROM {$table}")->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $columnList = implode(', ', $columns);

                    foreach ($rows as $row) {
                        $values = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote($val);
                        }, array_values($row));

                        $dump .= "INSERT INTO {$table} ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $dump .= "\n";
                }
            }
        }

        file_put_contents($filepath, $dump);
        $this->info("✓ SQLite dump created: {$filename}");
        $this->comment("  Location: {$filepath}");
        $this->comment("  Size: " . formatBytes(filesize($filepath)));
    }

    private function dumpMySQL($includeData)
    {
        $dumpDir = database_path('dumps');
        if (!is_dir($dumpDir)) {
            mkdir($dumpDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $filename = "epsilon_mysql_{$timestamp}.sql";
        $filepath = "{$dumpDir}/{$filename}";

        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $mysqldump = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s --routines --triggers --events --single-transaction --default-character-set=utf8mb4 %s > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($database),
            escapeshellarg($filepath)
        );

        $output = null;
        $exitCode = null;
        exec($mysqldump, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \Exception("mysqldump failed with exit code {$exitCode}");
        }

        $this->info("✓ MySQL dump created: {$filename}");
        $this->comment("  Location: {$filepath}");
        $this->comment("  Size: " . formatBytes(filesize($filepath)));
    }
}

// Helper function
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
