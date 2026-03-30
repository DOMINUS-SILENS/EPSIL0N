<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use App\Models\ProjectionVersion;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class CreditPolicyProjector extends Projector
{
    protected string $table = 'credit_policy_projections';

    protected function getVersionFromDatabase(): int
    {
        return ProjectionVersion::where('projector_name', self::class)->value('version') ?? 0;
    }

    protected function setVersion(int $version): void
    {
        ProjectionVersion::updateOrCreate(['projector_name' => self::class], ['version' => $version]);
    }

    public function handleCustomerCreditLimitUpdated(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)->updateOrInsert(
            [
                'entreprise_id' => $payload['entreprise_id'] ?? 1,
                'customer_id' => $payload['customer_id']
            ],
            [
                'credit_limit' => $payload['credit_limit'],
                'tolerance_days' => $payload['tolerance_days'] ?? 0,
                'is_blocked' => false,
                'block_reason' => null,
                'updated_at' => now(),
            ]
        );
    }

    public function handleCreditBlockActivated(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)->where('entreprise_id', $payload['entreprise_id'] ?? 1)
            ->where('customer_id', $payload['customer_id'])
            ->update([
                'is_blocked' => true,
                'block_reason' => $payload['reason'] ?? 'System automated block',
                'updated_at' => now(),
            ]);
    }
    
    public function handleCreditBlockLifted(array $payload, DomainOutbox $event): void
    {
        DB::table($this->table)->where('entreprise_id', $payload['entreprise_id'] ?? 1)
            ->where('customer_id', $payload['customer_id'])
            ->update([
                'is_blocked' => false,
                'block_reason' => null,
                'updated_at' => now(),
            ]);
    }
}
