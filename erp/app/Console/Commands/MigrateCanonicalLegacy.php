<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Phase 2B: Idempotent Migration Engine (Hardened v10)
 * Strictly Infra-Only: No Eloquent Models, No Observers, No Factories.
 */
class MigrateCanonicalLegacy extends Command
{
    protected $signature = 'canonical:migrate-legacy 
                            {--wave= : The migration wave to execute (1, 2, or 3)}
                            {--dry-run : Simulate the migration without writing}
                            {--execute : Perform the actual writes}';

    protected $description = 'Migrate legacy data to canonical tables using infra-level Query Builder.';

    /**
     * Primary Key Mapping for Legacy Tables
     */
    protected $primaryKeys = [
        'entreprise' => 'entreprise_id',
        'article' => 'article_id',
        'depot' => 'depot_id',
        'customers' => 'id',
        'orders' => 'id',
        'order_lines' => 'id',
        'balance_stock' => ['article_id', 'date_day'], // Composite PK
        'device_sync_state' => 'id',
    ];

    private $audit = [
        'migrated' => 0,
        'skipped' => 0,
        'failed' => 0,
    ];

    public function handle()
    {
        $wave = $this->option('wave');
        $execute = $this->option('execute');

        if (!$wave) {
            $this->error('Please specify a --wave (1, 2, or 3).');
            return 1;
        }

        if (!$execute && !$this->option('dry-run')) {
            $this->info('Dry-run mode. Use --execute to perform actual writes.');
        }

        $this->info("Starting Wave {$wave} Migration (Hardened v10)...");

        switch ($wave) {
            case 1:
                $this->migrateWave1($execute);
                break;
            case 2:
                $this->migrateWave2($execute);
                break;
            case 3:
                $this->migrateWave3($execute);
                break;
            default:
                $this->error("Invalid wave: {$wave}");
                return 1;
        }

        $this->table(['Status', 'Count'], [
            ['Migrated', $this->audit['migrated']],
            ['Skipped', $this->audit['skipped']],
            ['Failed', $this->audit['failed']],
        ]);

        return 0;
    }

    /**
     * Wave 1: Referentials (Enterprises, Articles, Depots, Customers)
     */
    private function migrateWave1($execute)
    {
        $identity = app(\App\Services\CanonicalIdentityService::class);

        // 1. Enterprises
        $this->migrateTable('entreprise', 'canonical_entreprises', function($row) {
            return [
                'id' => $row->entreprise_id,
                'nom' => $row->nom,
                'raison_sociale' => $row->raison_sociale,
                'adresse' => $row->adresse,
                'telephone' => $row->telephone,
                'email' => $row->email,
                'source_legacy_id' => (string) $row->entreprise_id,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);

        // 2. Articles
        $this->migrateTable('article', 'canonical_articles', function($row) {
            return [
                'id' => $row->article_id,
                'entreprise_id' => $row->entreprise_id,
                'designation' => $row->designation ?: '[UNNAMED ARTICLE]',
                'sku' => $row->article_product_number,
                'ean13' => $row->ean13,
                'source_legacy_id' => (string) $row->article_id,
                'source_system' => 'legacy',
                'is_active' => (bool) ($row->active ?? true),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);

        // 3. Depots
        $this->migrateTable('depot', 'canonical_depots', function($row) {
            return [
                'id' => $row->depot_id,
                'entreprise_id' => $row->entreprise_id,
                'designation' => $row->designation ?: '[DEFAULT DEPOT]',
                'code' => $row->depot_code_barre,
                'source_legacy_id' => (string) $row->depot_id,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);

        // 4. Customers
        $this->migrateTable('customers', 'canonical_customers', function($row) {
            return [
                'id' => $row->id,
                'entreprise_id' => $row->entreprise_id,
                'name' => $row->name ?: '[UNNAMED CUSTOMER]',
                'credit_limit' => (float)($row->credit_limit ?? 0),
                'source_legacy_id' => (string) $row->id,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);
    }

    /**
     * Wave 2: Infra (Sync States, Balances)
     */
    private function migrateWave2($execute)
    {
        // 1. Sync States
        $this->migrateTable('device_sync_state', 'canonical_device_sync_states', function($row) {
            return [
                'id' => $row->id,
                'entreprise_id' => $row->entreprise_id ?? 1,
                'device_id' => $row->device_id ?? 'unknown',
                'entity_type' => $row->entity_type,
                'last_sync_at' => $row->last_sync_at,
                'last_sync_sequence' => 0,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);

        // 2. Stock Balances (Composite Unique Key: entreprise_id, depot_id, article_id)
        $this->migrateTable('balance_stock', 'canonical_stock_balances', function($row) {
            return [
                'entreprise_id' => $row->entreprise_id ?? 1,
                'depot_id' => $row->depot_id ?? 1,
                'article_id' => $row->article_id,
                'available_quantity' => (float)($row->quantite_theorique ?? 0),
                'reserved_quantity' => (float)($row->quantite_reservee ?? 0),
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);
    }

    /**
     * Wave 3: Transactional (Orders & Lines with Deterministic UUIDs)
     */
    private function migrateWave3($execute)
    {
        $identity = app(\App\Services\CanonicalIdentityService::class);

        // 1. Orders
        $this->migrateTable('orders', 'canonical_orders', function($row) use ($identity) {
            $entrepriseId = $row->entreprise_id ?? 1;
            return [
                'id' => $identity->generateId('order', $entrepriseId, $row->id),
                'entreprise_id' => $entrepriseId,
                'customer_id' => $row->customer_id,
                'order_number' => $row->reference ?? 'ORD-'.$row->id,
                'status' => $row->status ?? 'closed',
                'ordered_at' => $row->created_at ?? now(),
                'subtotal_amount' => (float)($row->subtotal_amount ?? 0),
                'total_amount' => (float)($row->total_amount ?? 0),
                'source_legacy_id' => (string) $row->id,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);

        // 2. Order Lines (Deterministic UUIDv5)
        $this->migrateTable('order_lines', 'canonical_order_lines', function($row) use ($identity) {
            $entrepriseId = 1; // Fallback or lookup from order
            
            $canonicalOrderId = $identity->generateId('order', $entrepriseId, $row->order_id);
            $canonicalId = $identity->generateId('order_line', $entrepriseId, $row->id, [
                'legacy_order_id' => $row->order_id
            ]);

            $article = DB::table('article')->where('article_id', $row->product_id)->first();
            
            return [
                'id' => $canonicalId,
                'order_id' => $canonicalOrderId,
                'article_id' => $row->product_id,
                'snapshot_designation' => $article->designation ?? 'Unknown Product',
                'snapshot_sku' => $article->article_product_number ?? null,
                'snapshot_ean13' => $article->ean13 ?? null,
                'quantity' => (float)($row->quantity ?? 0),
                'unit_price' => (float)($row->unit_price ?? 0),
                'line_total' => (float)($row->total ?? 0),
                'source_legacy_id' => (string) $row->id,
                'source_legacy_order_id' => (string) $row->order_id,
                'source_system' => 'legacy',
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];
        }, $execute);
    }

    /**
     * Generic Idempotent Table Migrator (Hardened PK Mapping)
     */
    private function migrateTable($source, $target, $transformer, $execute)
    {
        $this->comment("Migrating {$source} to {$target}...");
        
        $pk = $this->primaryKeys[$source] ?? 'id';
        $query = DB::table($source);

        if (is_array($pk)) {
            foreach ($pk as $col) {
                $query->orderBy($col);
            }
        } else {
            $query->orderBy($pk);
        }

        $query->chunk(500, function($rows) use ($source, $target, $transformer, $pk, $execute) {
            foreach ($rows as $row) {
                $data = $transformer($row);
                
                // Deterministic Existence Check via source_legacy_id or composite triplet
                $checkQuery = DB::table($target);
                
                if ($target === 'canonical_stock_balances') {
                    $checkQuery->where([
                        'entreprise_id' => $data['entreprise_id'],
                        'depot_id' => $data['depot_id'],
                        'article_id' => $data['article_id'],
                    ]);
                } elseif ($target === 'canonical_order_lines') {
                    $checkQuery->where([
                        'order_id' => $data['order_id'],
                        'source_legacy_id' => $data['source_legacy_id'],
                    ]);
                } elseif (isset($data['source_legacy_id'])) {
                    $checkQuery->where('source_legacy_id', $data['source_legacy_id']);
                } elseif (is_array($pk)) {
                    foreach ($pk as $col) {
                        $checkQuery->where($col, $row->$col);
                    }
                } else {
                    $checkQuery->where($pk, $row->$pk);
                }

                if ($checkQuery->exists()) {
                    $this->audit['skipped']++;
                    continue;
                }

                if ($execute) {
                    try {
                        DB::table($target)->insert($data);
                        $this->audit['migrated']++;
                    } catch (\Exception $e) {
                        Log::error("Failed to migrate row from {$source} to {$target}: " . $e->getMessage());
                        $this->error("Failed to migrate row: " . $e->getMessage());
                        $this->audit['failed']++;
                    }
                } else {
                    $this->audit['migrated']++;
                }
            }
        });
    }
}
