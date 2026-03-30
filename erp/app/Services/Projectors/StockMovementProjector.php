<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Models\StockMove;
use App\Services\Projector;

class StockMovementProjector extends Projector
{
    protected string $table = 'stock_moves';

    protected string $idField = 'id';

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

    protected function getState(int $aggregateId): array
    {
        return [];
    }

    protected function restoreState(int $aggregateId, array $state): void {}

    protected function setLastProcessedEventId(int $aggregateId, int $lastEventId): void {}

    public function handleStockEntered(array $payload): void
    {
        StockMove::create([
            'product_id' => $payload['article_id'],
            'warehouse_id' => $payload['depot_id_destination'],
            'qty' => $payload['quantity'],
            'type' => 'in',
            'reference' => $payload['additional_data']['reference'] ?? null,
            'reference_id' => $payload['additional_data']['reference_id'] ?? null,
            'moved_at' => now(),
        ]);
    }

    public function handleStockExited(array $payload): void
    {
        StockMove::create([
            'product_id' => $payload['article_id'],
            'warehouse_id' => $payload['depot_id_source'],
            'qty' => $payload['quantity'],
            'type' => 'out',
            'reference' => $payload['additional_data']['reference'] ?? null,
            'reference_id' => $payload['additional_data']['reference_id'] ?? null,
            'moved_at' => now(),
        ]);
    }

    public function handleStockTransferred(array $payload): void
    {
        StockMove::create([
            'product_id' => $payload['article_id'],
            'warehouse_id' => $payload['depot_id_source'],
            'qty' => $payload['quantity'],
            'type' => 'out',
            'reference' => $payload['additional_data']['reference'] ?? null,
            'reference_id' => $payload['additional_data']['reference_id'] ?? null,
            'moved_at' => now(),
        ]);

        StockMove::create([
            'product_id' => $payload['article_id'],
            'warehouse_id' => $payload['depot_id_destination'],
            'qty' => $payload['quantity'],
            'type' => 'in',
            'reference' => $payload['additional_data']['reference'] ?? null,
            'reference_id' => $payload['additional_data']['reference_id'] ?? null,
            'moved_at' => now(),
        ]);
    }
}
