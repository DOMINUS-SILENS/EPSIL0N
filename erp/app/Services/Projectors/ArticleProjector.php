<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;
use App\Services\CanonicalIdentityService;
use App\Services\CanonicalSyncService;

class ArticleProjector extends Projector
{
    protected string $table = 'articles';
    protected string $idField = 'article_id';

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
        DB::table('articles')->truncate();
        if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
            DB::table('canonical_articles')->truncate();
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

    public function handleArticleCreated(array $payload, DomainOutbox $event): void
    {
        // 1. Insert Base Article with Idempotent Upsert Guards
        $articleExists = DB::table('articles')
            ->where('entreprise_id', $payload['entrepriseId'])
            ->where('article_id', $payload['articleId'])
            ->exists();

        if (!$articleExists) {
            $insertData = [
                'entreprise_id' => $payload['entrepriseId'],
                'article_id' => $payload['articleId'],
                ...$payload['data'],
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('articles')->insert($insertData);

            // Dual-Write to Canonical Schema
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                $canonicalId = $this->identity->generateId('article', $payload['entrepriseId'], $payload['articleId']);

                $this->sync->sync('canonical_articles', $payload['entrepriseId'], $payload['articleId'], [
                    'id' => $canonicalId,
                    'designation' => $payload['data']['designation'] ?? ($payload['data']['designation'] ?? '[UNNAMED ARTICLE]'),
                    'sku' => $payload['data']['article_product_number'] ?? ($payload['data']['sku'] ?? null),
                    'ean13' => $payload['data']['ean13'] ?? ($payload['data']['ean13'] ?? null),
                    'barcode' => $payload['data']['barcode'] ?? ($payload['data']['barcode'] ?? null),
                    'is_active' => (bool)($payload['data']['active'] ?? true),
                ]);
            }
        }

        // 2. Insert Associated Units array iteration
        foreach ($payload['units'] ?? [] as $unit) {
            $unitExists = DB::table('article_unite')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('article_id', $payload['articleId'])
                ->where('article_unite_id', $unit['article_unite_id'])
                ->exists();

            if (!$unitExists) {
                $unitData = [
                    'entreprise_id' => $payload['entrepriseId'],
                    'article_id' => $payload['articleId'],
                    'article_unite_id' => $unit['article_unite_id'],
                    'article_prix_vente' => $unit['article_prix_vente'] ?? 0,
                    // Assume other specific unit fields mapped inside $unit...
                    'created_at' => now(),
                    'updated_at' => now(),
                    'last_event_id' => $event->id
                ];
                DB::table('article_unite')->insert($unitData);

                // Dual-Write to Canonical Schema
                if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                    $this->sync->sync('article_units', $payload['entrepriseId'], $unit['article_unite_id'], [
                        'article_id' => $payload['articleId'],
                        'barcode' => $unit['barcode'] ?? null,
                        'price_selling' => (float)($unit['article_prix_vente'] ?? 0),
                    ]);
                }
            }
        }

        // 3. Insert Taxes links
        if (!empty($payload['taxes'])) {
            $taxLinks = array_map(fn($taxId) => [
            'entreprise_id' => $payload['entrepriseId'],
            'article_id' => $payload['articleId'],
            'taxe_id' => $taxId,
            'created_at' => now(),
            'updated_at' => now()
            ], $payload['taxes']);

            DB::table('article_taxe')->insertOrIgnore($taxLinks);
        }
    }

    public function handleArticleUnitsUpdated(array $payload, DomainOutbox $event): void
    {
        foreach ($payload['unitUpdates'] as $update) {
            DB::table('article_unite')
                ->where('entreprise_id', $payload['entrepriseId'])
                ->where('article_id', $payload['articleId'])
                ->where('article_unite_id', $update['article_unite_id'])
                ->where('last_event_id', '<', $event->id)
                ->update([
                'article_prix_vente' => $update['new_price'] ?? null,
                'last_event_id' => $event->id,
                'updated_at' => now(),
            ]);

            // Dual-Write to Canonical Schema
            if (config('epsilon.canonical_dual_write', false) || env('CANONICAL_DUAL_WRITE', false)) {
                DB::table('article_units')
                    ->where('id', $update['article_unite_id'])
                    ->update([
                        'price_selling' => (float)($update['new_price'] ?? 0),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
