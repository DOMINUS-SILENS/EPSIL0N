<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class MovementProjector extends Projector
{
    protected string $table = 'mouvements';
    protected string $idField = 'mouvement_id';
    
    protected CanonicalIdentityService $identity;
    protected CanonicalSyncService $sync;

    public function __construct()
    {
        $this->identity = app(CanonicalIdentityService::class);
        $this->sync = app(CanonicalSyncService::class);
    }

    /** @var array Cache for mapping movement IDs to canonical Order UUIDs for dual-write */
    protected array $orderIdCache = [];

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

    public function handleMovementCreated(array $payload, DomainOutbox $event): void
    {
        $exists = DB::table('mouvements')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('mouvement_id', $payload['movementId'])
            ->exists();

        if (!$exists) {
            $insertData = [
                'entreprise_id' => $payload['entrepriseId'],
                'mouvement_id' => $payload['movementId'],
                ...$payload['data'],
                'status' => 'draft',
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('mouvements')->insert($insertData);

            // Dual-Write to Canonical Schema
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                $orderId = $this->identity->generateId('order', $payload['entrepriseId'], $payload['movementId']);

                $this->sync->sync('canonical_orders', $payload['entrepriseId'], $payload['movementId'], [
                    'id' => $orderId,
                    'customer_id' => $payload['contactId'] ?? ($payload['data']['contact_id'] ?? 0),
                    'order_number' => $payload['data']['reference'] ?? 'MOV-' . $payload['movementId'],
                    'status' => 'draft',
                    'ordered_at' => $insertData['created_at'],
                    'subtotal_amount' => (float)($payload['data']['subtotal_amount'] ?? 0),
                    'total_amount' => (float)($payload['data']['total_amount'] ?? 0),
                ]);

                // Store order memory for line items
                $this->orderIdCache[$payload['movementId']] = $orderId;
            }
        }

        foreach ($payload['lines'] ?? [] as $line) {
            $lineData = [
                'entreprise_id' => $payload['entrepriseId'],
                'mouvement_id' => $payload['movementId'],
                'mouvement_ligne_id' => $line['mouvement_ligne_id'],
                'article_id' => $line['article_id'],
                'quantite' => $line['quantite'],
                ...$line,
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('mouvement_lignes')->insertOrIgnore($lineData);

            // Dual-Write to Canonical Schema
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                $orderId = $this->orderIdCache[$payload['movementId']] ?? null;
                if (!$orderId) {
                    $orderId = $this->identity->generateId('order', $payload['entrepriseId'], $payload['movementId']);
                }

                if ($orderId) {
                    $lineId = $this->identity->generateId('order_line', $payload['entrepriseId'], $line['mouvement_ligne_id'], [
                        'legacy_order_id' => $payload['movementId']
                    ]);

                    $this->sync->sync('canonical_order_lines', $payload['entrepriseId'], $line['mouvement_ligne_id'], [
                        'id' => $lineId,
                        'order_id' => $orderId,
                        'article_id' => $line['article_id'],
                        'snapshot_designation' => $line['designation'] ?? 'Unknown Article',
                        'quantity' => (float)($line['quantite'] ?? 0),
                        'unit_price' => (float)($line['prix_unitaire'] ?? 0),
                        'line_total' => (float)(($line['quantite'] ?? 0) * ($line['prix_unitaire'] ?? 0)),
                        'source_legacy_order_id' => (string) $payload['movementId'],
                    ]);
                }
            }
        }
    }

    private function updateMovementStatus(int $entrepriseId, int $movementId, int $eventId, string $status): void
    {
        DB::table('mouvements')
            ->where('entreprise_id', $entrepriseId)
            ->where('mouvement_id', $movementId)
            ->where('last_event_id', '<', $eventId)
            ->update([
                'status' => $status,
                'last_event_id' => $eventId,
                'updated_at' => now(),
            ]);

        // Dual-Write to Canonical Schema
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            $this->sync->sync('canonical_orders', $entrepriseId, $movementId, [
                'status' => $status,
            ]);
        }
    }

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        $this->updateMovementStatus($payload['entrepriseId'], $payload['movementId'], $event->id, 'validated');
    }

    public function handleMovementDelivered(array $payload, DomainOutbox $event): void
    {
        $this->updateMovementStatus($payload['entrepriseId'], $payload['movementId'], $event->id, 'delivered');
    }

    public function handleMovementCancelled(array $payload, DomainOutbox $event): void
    {
        $this->updateMovementStatus($payload['entrepriseId'], $payload['movementId'], $event->id, 'cancelled');
    }
}
