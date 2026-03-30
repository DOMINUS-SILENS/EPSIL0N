<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;
use App\Services\CanonicalIdentityService;
use App\Services\CanonicalSyncService;

class ContactProjector extends Projector
{
    protected string $table = 'contacts';
    protected string $idField = 'contact_id';

    protected CanonicalIdentityService $identity;
    protected CanonicalSyncService $sync;

    public function __construct()
    {
        $this->identity = app(CanonicalIdentityService::class);
        $this->sync = app(CanonicalSyncService::class);
    }

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(
            ['projector_name' => self::class],
            ['version' => $version]
        );
    }

    public function resetState(): void
    {
        DB::table('contacts')->truncate();
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_customers')->truncate();
        }
    }

    protected function getState(int $aggregateId): array
    {
        return [];
    }

    protected function restoreState(int $aggregateId, array $state): void
    {
    }

    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void
    {
    }

    public function handleContactCreated(array $payload, DomainOutbox $event): void
    {
        $affected = DB::table('contacts')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('contact_id', $payload['contactId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'contact_nom' => $payload['nom'] ?? null,
                'contact_prenom' => $payload['prenom'] ?? null,
                'entreprise_id' => $payload['entrepriseId'] ?? null,
                'contact_raison_sociale' => $payload['raisonSociale'] ?? null,
                'last_event_id' => $event->id,
                'updated_at' => now(),
            ]);

        if ($affected === 0) {
            $exists = DB::table('contacts')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('contact_id', $payload['contactId'])
                ->exists();

            if (!$exists) {
                $insertData = [
                    'entreprise_id' => $payload['entrepriseId'],
                    'contact_id' => $payload['contactId'],
                    'contact_nom' => $payload['nom'] ?? null,
                    'contact_prenom' => $payload['prenom'] ?? null,
                    'entreprise_id' => $payload['entrepriseId'] ?? null,
                    'contact_raison_sociale' => $payload['raisonSociale'] ?? null,
                    'last_event_id' => $event->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                DB::table('contacts')->insert($insertData);

                // Dual-Write to Canonical Schema
                if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                    $canonicalId = $this->identity->generateId('customer', $payload['entrepriseId'], $payload['contactId']);

                    $this->sync->sync('canonical_customers', $payload['entrepriseId'], $payload['contactId'], [
                        'id' => $canonicalId,
                        'name' => trim(($payload['nom'] ?? '') . ' ' . ($payload['prenom'] ?? '')) ?: ($payload['raisonSociale'] ?? '[UNNAMED CUSTOMER]'),
                        'credit_limit' => (float)($payload['credit_limit'] ?? 0),
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    public function handleContactUpdated(array $payload, DomainOutbox $event): void
    {
        // Re-use logic since it mirrors idempotent UPSERT state changes exactly
        $this->handleContactCreated($payload, $event);
    }

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        // Only increase credit debt if this movement relies on deferred terminology
        // Normally determined by a `deferred_payment` flag via payload. Emulating broadly here:
        $orderTotal = collect($payload['lines'])->sum(function($l) {
            return ($l['quantite'] ?? 0) * ($l['prix_unitaire'] ?? 0);
        });

        // Use ContactId from metadata array attached to standard Movement payload headers
        $contactId = $payload['contactId'] ?? null;
        
        if ($contactId && $orderTotal > 0) {
            DB::table('contacts')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('contact_id', $contactId)
                ->where('last_event_id', '<', $event->id)
                ->update([
                    'montant_credit_en_cours' => DB::raw("montant_credit_en_cours + {$orderTotal}"),
                    'last_event_id' => $event->id,
                    'updated_at' => now(),
                ]);
        }
    }

    public function handlePaymentRecorded(array $payload, DomainOutbox $event): void
    {
        // Reduce the customer's outstanding balance unconditionally ensuring cash flows zero-out limits
        $amount = (float) $payload['amount'];
        
        DB::table('contacts')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('contact_id', $payload['contactId'])
            ->where('last_event_id', '<', $event->id)
            ->update([
                'montant_credit_en_cours' => DB::raw("montant_credit_en_cours - {$amount}"),
                'last_event_id' => $event->id,
                'updated_at' => now()
            ]);
    }
}
