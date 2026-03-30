<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MigrateCanonicalData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'canonical:migrate-data {--force : Force the migration even if canonical tables are not empty}';

    /**
     * The console command description.
     */
    protected $description = 'Migrates legacy data to the Canonical Schema with strict audit and type-casting.';

    /**
     * Anomaly log collector
     */
    protected array $anomalies = [];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting Canonical Data Migration (Parallel Rebuild)...');

        $this->migrateTable('entreprises', 'entreprise', function ($row) {
            return [
                'id' => $row->entreprise_id,
                'nom' => trim($row->nom),
                'raison_sociale' => trim($row->raison_sociale),
                'adresse' => $row->adresse,
                'telephone' => $row->telephone,
                'email' => $row->email,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        });

        $this->migrateTable('articles', 'article', function ($row) {
            if (!$row->designation) {
                $this->logAnomaly('articles', $row->article_id, 'REJECTED: Missing designation');
                return null;
            }

            return [
                'id' => $row->article_id,
                'entreprise_id' => $row->entreprise_id ?? 1,
                'designation' => trim($row->designation),
                'abreviation' => trim($row->article_abreviation),
                'ean13' => substr(trim($row->ean13), 0, 32),
                'barcode' => substr(trim($row->barcode), 0, 64),
                'stock_quantity' => number_format((float) ($row->quantite_stock ?? 0), 3, '.', ''),
                'min_quantity' => number_format((float) ($row->quantite_min ?? 0), 3, '.', ''),
                'optimal_quantity' => number_format((float) ($row->article_quantite_optimale ?? 0), 3, '.', ''),
                'price_selling' => number_format((float) ($row->article_prix_vente ?? 0), 2, '.', ''),
                'is_stock_managed' => (bool) ($row->is_stock_managed ?? true),
                'is_active' => (bool) ($row->active ?? true),
                'is_archived' => (bool) ($row->archive ?? false),
                'created_at' => $row->article_created_date ?? $row->created_at ?? now(),
                'updated_at' => $row->article_updated_date ?? $row->updated_at ?? now(),
            ];
        });

        $this->migrateTable('depots', 'depot', function ($row) {
            return [
                'id' => $row->depot_id,
                'entreprise_id' => $row->entreprise_id ?? 1,
                'designation' => trim($row->designation),
                'code' => trim($row->depot_ean13),
                'address' => $row->adresse,
                'is_active' => (bool) ($row->is_used ?? true),
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        });

        $this->migrateTable('device_sync_states', 'device_sync_state', function ($row) {
            $lastSyncAt = null;
            if ($row->last_sync_timestamp) {
                try {
                    $lastSyncAt = is_numeric($row->last_sync_timestamp) 
                        ? Carbon::createFromTimestamp($row->last_sync_timestamp)
                        : Carbon::parse($row->last_sync_timestamp);
                } catch (\Exception $e) {
                    $this->logAnomaly('device_sync_states', $row->id, 'INVALID DATE: ' . $row->last_sync_timestamp);
                }
            }

            return [
                'id' => $row->id,
                'entreprise_id' => $row->entreprise_id ?? 1,
                'device_id' => substr(trim($row->device_id), 0, 128),
                'entity_type' => trim($row->entity_type),
                'last_sync_at' => $lastSyncAt,
                'last_sync_sequence' => $row->sync_count ?? 0,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ];
        });

        $this->info('Migration completed with ' . count($this->anomalies) . ' anomalies logged.');
    }

    /**
     * Generic table migration with chunking
     */
    protected function migrateTable(string $targetTable, string $sourceTable, callable $mapper): void
    {
        if (!DB::getSchemaBuilder()->hasTable($sourceTable)) {
            $this->warn("Source table {$sourceTable} does not exist. Skipping...");
            return;
        }

        $count = DB::table($sourceTable)->count();
        $this->info("Migrating {$sourceTable} -> {$targetTable} ({$count} records)");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        DB::table($sourceTable)->orderBy('id')->chunkById(200, function ($rows) use ($targetTable, $mapper, $bar) {
            $data = [];
            foreach ($rows as $row) {
                $mapped = $mapper($row);
                if ($mapped) {
                    $data[] = $mapped;
                }
                $bar->advance();
            }
            DB::table($targetTable)->insert($data);
        }, $this->getPrimaryKeyForTable($sourceTable));

        $bar->finish();
        $this->newLine();
    }

    /**
     * Helper to log anomalies
     */
    protected function logAnomaly(string $table, $id, string $message): void
    {
        $anomaly = "[{$table} id:{$id}] {$message}";
        $this->anomalies[] = $anomaly;
        Log::channel('canonical_migration')->warning($anomaly);
    }

    /**
     * Get primary key for legacy tables (some use table_id prefix)
     */
    protected function getPrimaryKeyForTable(string $table): string
    {
        return match ($table) {
            'article' => 'article_id',
            'depot' => 'depot_id',
            'entreprise' => 'entreprise_id',
            default => 'id',
        };
    }
}
