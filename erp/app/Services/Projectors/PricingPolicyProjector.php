<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class PricingPolicyProjector extends Projector
{
    protected string $table = 'pricing_projections';

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => $version]);
    }

    public function handlePricingPolicyPublished(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)->updateOrInsert(
            [
                'entreprise_id' => $payload['entreprise_id'] ?? 1,
                'policy_id' => $payload['policy_id']
            ],
            [
                'rules' => json_encode($payload['rules'] ?? []),
                'territory_id' => $payload['territory_id'] ?? null,
                'active_from' => $payload['active_from'] ?? now(),
                'active_until' => $payload['active_until'] ?? null,
                'updated_at' => now(),
            ]
        );
    }

    public function handlePromotionActivated(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)->updateOrInsert(
            [
                'entreprise_id' => $payload['entreprise_id'] ?? 1,
                'policy_id' => 'PROMO_' . $payload['promotion_id']
            ],
            [
                'rules' => json_encode($payload['promotion_details'] ?? []),
                'territory_id' => null, // Global or targeted
                'active_from' => $payload['start_date'] ?? now(),
                'active_until' => $payload['end_date'] ?? null,
                'updated_at' => now(),
            ]
        );
    }
}
