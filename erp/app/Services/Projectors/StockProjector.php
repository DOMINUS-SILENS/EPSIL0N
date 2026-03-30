<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;
use App\Services\CanonicalIdentityService;
use App\Services\CanonicalSyncService;

/**
 * Optimized Stock Projector with Batch Processing
 * Records stock movements and maintains balance_stock materialized view
 */
class StockProjector extends Projector
{
    protected string $table = 'article_mouvement';
    protected string $idField = 'article_mouvement_id';

    protected CanonicalIdentityService $identity;
    protected CanonicalSyncService $sync;

    public function __construct()
    {
        $this->identity = app(CanonicalIdentityService::class);
        $this->sync = app(CanonicalSyncService::class);
    }

    /** @var array Batch buffer for stock movements */
    protected array $movementBuffer = [];

    /** @var array Balance updates to apply after batch */
    protected array $balanceUpdates = [];

    /** @var int Batch size for movements */
    protected int $batchSize = 100;

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => $version]);
    }

    protected function getState(int $aggregateId): array { return []; }
    protected function restoreState(int $aggregateId, array $state): void {}
    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void {}

    /**
     * Override flushBatch for bulk inserts
     */
    protected function flushBatch(): void
    {
        if (empty($this->movementBuffer)) {
            return;
        }

        // Bulk insert movements
        DB::table($this->table)->insert($this->movementBuffer);
        $this->movementBuffer = [];

        // Update balance_stock in bulk
        $this->flushBalanceUpdates();
    }

    /**
     * Apply accumulated balance updates
     */
    protected function flushBalanceUpdates(): void
    {
        if (empty($this->balanceUpdates)) {
            return;
        }

        $dualWrite = config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false);

        foreach ($this->balanceUpdates as $key => $update) {
            DB::table('balance_stock')->updateOrInsert(
                [
                    'entreprise_id' => $update['entreprise_id'],
                    'depot_id' => $update['depot_id'],
                    'article_id' => $update['article_id'],
                ],
                [
                    'quantite_disponible' => DB::raw('COALESCE(quantite_disponible, 0) + ' . $update['delta']),
                    'quantite_theorique' => DB::raw('COALESCE(quantite_theorique, 0) + ' . $update['delta']),
                    'updated_at' => now(),
                ]
            );

            // Dual-Write to Canonical Schema
            if ($dualWrite) {
                DB::table('canonical_stock_balances')->updateOrInsert(
                    [
                        'entreprise_id' => $update['entreprise_id'],
                        'depot_id' => $update['depot_id'],
                        'article_id' => $update['article_id'],
                    ],
                    [
                        'available_quantity' => DB::raw('COALESCE(available_quantity, 0) + ' . $update['delta']),
                        'source_system' => 'legacy',
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->balanceUpdates = [];
    }

    /**
     * Queue a stock movement - handles batch or immediate mode
     */
    private function queueMovement(int $eventId, int $entrepriseId, int $depotId, int $articleId, float $qty, string $type, string $reason): void
    {
        $movement = [
            'entreprise_id' => $entrepriseId,
            'depot_id' => $depotId,
            'article_id' => $articleId,
            'type_mouvement' => $type,
            'quantite' => abs($qty),
            'motif' => $reason,
            'last_event_id' => $eventId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($this->batchMode) {
            $this->movementBuffer[] = $movement;

            // Track balance update
            $key = "{$entrepriseId}:{$depotId}:{$articleId}";
            $delta = $type === '+' ? $qty : -$qty;

            if (!isset($this->balanceUpdates[$key])) {
                $this->balanceUpdates[$key] = [
                    'entreprise_id' => $entrepriseId,
                    'depot_id' => $depotId,
                    'article_id' => $articleId,
                    'delta' => $delta,
                ];
            } else {
                $this->balanceUpdates[$key]['delta'] += $delta;
            }

            // Flush if buffer is full
            if (count($this->movementBuffer) >= $this->batchSize) {
                DB::table($this->table)->insert($this->movementBuffer);
                $this->movementBuffer = [];
                $this->flushBalanceUpdates();
            }
        } else {
            // Immediate mode for non-batch processing
            DB::table($this->table)->insert($movement);

            // Update balance_stock
            $delta = $type === '+' ? $qty : -$qty;
            DB::table('balance_stock')->updateOrInsert(
                [
                    'entreprise_id' => $entrepriseId,
                    'depot_id' => $depotId,
                    'article_id' => $articleId,
                ],
                [
                    'quantite_disponible' => DB::raw("COALESCE(quantite_disponible, 0) + {$delta}"),
                    'quantite_theorique' => DB::raw("COALESCE(quantite_theorique, 0) + {$delta}"),
                    'updated_at' => now(),
                ]
            );

            // Immediate Dual-Write to Canonical Schema
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                DB::table('canonical_stock_balances')->updateOrInsert(
                    [
                        'entreprise_id' => $entrepriseId,
                        'depot_id' => $depotId,
                        'article_id' => $articleId,
                    ],
                    [
                        'available_quantity' => DB::raw("COALESCE(available_quantity, 0) + {$delta}"),
                        'source_system' => 'legacy',
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Legacy method for single event processing
     */
    private function recordLinearAudit($event, int $entrepriseId, int $depotId, int $articleId, float $qty, string $type, string $reason): void
    {
        $eventId = $event instanceof DomainOutbox ? $event->id : ($event['id'] ?? 0);
        $this->queueMovement($eventId, $entrepriseId, $depotId, $articleId, $qty, $type, $reason);
    }

    public function handleStockReceived(array $payload, DomainOutbox $event): void
    {
        $this->recordLinearAudit(
            $event,
            $payload['entrepriseId'],
            $payload['depotId'],
            $payload['articleId'],
            $payload['quantity'],
            '+',
            $payload['reason'] ?? 'Réception'
        );
    }

    public function handleStockConsumed(array $payload, DomainOutbox $event): void
    {
        $this->recordLinearAudit(
            $event,
            $payload['entrepriseId'],
            $payload['depotId'],
            $payload['articleId'],
            $payload['quantity'],
            '-',
            $payload['reason'] ?? 'Consommation'
        );
    }

    public function handleStockTransferred(array $payload, DomainOutbox $event): void
    {
        // Splits the transfer logically for accounting analytical trails
        $this->recordLinearAudit(
            $event,
            $payload['entrepriseId'],
            $payload['sourceDepotId'],
            $payload['articleId'],
            $payload['quantity'],
            '-',
            'Transfert Sortant'
        );
        $this->recordLinearAudit(
            $event,
            $payload['entrepriseId'],
            $payload['targetDepotId'],
            $payload['articleId'],
            $payload['quantity'],
            '+',
            'Transfert Entrant'
        );
    }

    public function handleStockAdjusted(array $payload, DomainOutbox $event): void
    {
        $type = $payload['delta'] >= 0 ? '+' : '-';
        $this->recordLinearAudit(
            $event,
            $payload['entrepriseId'],
            $payload['depotId'],
            $payload['articleId'],
            abs($payload['delta']),
            $type,
            'Ajustement Inventaire'
        );
    }

    /**
     * Handle stock reservation events
     */
    public function handleStockReserved(array $payload, DomainOutbox $event): void
    {
        // Update reserved quantity without changing available
        DB::table('balance_stock')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('depot_id', $payload['depotId'])
            ->where('article_id', $payload['articleId'])
            ->update([
                'quantite_reservee' => DB::raw("COALESCE(quantite_reservee, 0) + {$payload['quantity']}"),
                'updated_at' => now(),
            ]);

        // Dual-Write reserved quantity
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_stock_balances')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('depot_id', $payload['depotId'])
                ->where('article_id', $payload['articleId'])
                ->update([
                    'reserved_quantity' => DB::raw("COALESCE(reserved_quantity, 0) + {$payload['quantity']}"),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Handle stock reservation release
     */
    public function handleStockReservationReleased(array $payload, DomainOutbox $event): void
    {
        DB::table('balance_stock')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('depot_id', $payload['depotId'])
            ->where('article_id', $payload['articleId'])
            ->update([
                'quantite_reservee' => DB::raw("GREATEST(COALESCE(quantite_reservee, 0) - {$payload['quantity']}, 0)"),
                'updated_at' => now(),
            ]);

        // Dual-Write reserved release
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_stock_balances')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('depot_id', $payload['depotId'])
                ->where('article_id', $payload['articleId'])
                ->update([
                    'reserved_quantity' => DB::raw("GREATEST(COALESCE(reserved_quantity, 0) - {$payload['quantity']}, 0)"),
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reset state for rebuilds - includes balance_stock
     */
    public function resetState(): void
    {
        DB::table($this->table)->truncate();
        DB::table('balance_stock')->truncate();
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_stock_balances')->truncate();
        }
        ProjectionVersion::where('projector_name', self::class)->delete();
    }
}
