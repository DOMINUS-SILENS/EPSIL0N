<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class PromotionProjector extends Projector
{
    protected string $table = 'promotions';
    protected string $idField = 'promotion_id';

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

    public function handlePromotionCreated(array $payload, DomainOutbox $event): void
    {
        $exists = DB::table('promotions')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('promotion_id', $payload['promotionId'])
            ->exists();

        if (!$exists) {
            DB::table('promotions')->insert([
                'entreprise_id' => $payload['entrepriseId'],
                'promotion_id' => $payload['promotionId'],
                ...$payload['data'],
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Projecting specific article conditions table map
        foreach ($payload['conditions'] as $cond) {
            DB::table('promotion_article_commmande')->insertOrIgnore([
                'entreprise_id' => $payload['entrepriseId'],
                'promotion_id' => $payload['promotionId'],
                ...$cond,
                'last_event_id' => $event->id
            ]);
        }

        // Projecting rewards map natively
        foreach ($payload['rewards'] as $rew) {
            DB::table('promotion_article_gratuite')->insertOrIgnore([
                'entreprise_id' => $payload['entrepriseId'],
                'promotion_id' => $payload['promotionId'],
                ...$rew,
                'last_event_id' => $event->id
            ]);
        }

        // Projecting tiers
        foreach ($payload['tiers'] as $tier) {
            DB::table('promotion_palier')->insertOrIgnore([
                'entreprise_id' => $payload['entrepriseId'],
                'promotion_id' => $payload['promotionId'],
                ...$tier,
                'last_event_id' => $event->id
            ]);
        }
    }

    public function handlePromotionAppliedToOrder(array $payload, DomainOutbox $event): void
    {
        // Track the linkage structurally inside marketing projections tracking metric effectiveness
        DB::table('promotion_mouvement')->insertOrIgnore([
            'entreprise_id' => $payload['entrepriseId'],
            'movement_id' => $payload['movementId'],
            'promotion_id' => $payload['promotionId'],
            'applied_benefits' => json_encode($payload['appliedBenefits']),
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
