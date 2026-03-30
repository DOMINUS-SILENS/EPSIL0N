<?php

namespace App\Services\Projectors;

use App\Models\ProjectionVersion;
use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;
use Carbon\Carbon;

class SalesDashboardProjector extends Projector
{
    protected string $table = 'dashboard_sales';
    protected string $idField = 'route_id';

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

    public function handleMovementValidated(array $payload, DomainOutbox $event): void
    {
        $entrepriseId = $payload['entrepriseId'];
        $date = Carbon::parse($payload['date'] ?? now())->toDateString();
        $routeId = $payload['routeId'] ?? 0;
        $totalHt = $payload['totalHt'] ?? 0;
        $totalTtc = $payload['totalTtc'] ?? 0;

        // 1. Update dashboard_sales using secure UPSERT preventing race conditions
        DB::statement("
            INSERT INTO dashboard_sales (entreprise_id, date, route_id, subtotal_amount, total_amount, nb_orders, last_event_id, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                subtotal_amount = IF(last_event_id < VALUES(last_event_id), subtotal_amount + VALUES(subtotal_amount), subtotal_amount),
                total_amount = IF(last_event_id < VALUES(last_event_id), total_amount + VALUES(total_amount), total_amount),
                nb_orders = IF(last_event_id < VALUES(last_event_id), nb_orders + 1, nb_orders),
                updated_at = IF(last_event_id < VALUES(last_event_id), NOW(), updated_at),
                last_event_id = IF(last_event_id < VALUES(last_event_id), VALUES(last_event_id), last_event_id)
        ", [$entrepriseId, $date, $routeId, $totalHt, $totalTtc, $event->id]);

        // 2. Update dashboard_top_articles linearly
        $lines = $payload['lines'] ?? [];
        foreach ($lines as $line) {
            $articleId = $line['article_id'] ?? 0;
            $qty = $line['quantity'] ?? 0;
            $amountHt = $line['price_ht'] ?? 0;

            DB::statement("
                INSERT INTO dashboard_top_articles (entreprise_id, date, article_id, quantity_sold, amount_ht, last_event_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    quantity_sold = IF(last_event_id < VALUES(last_event_id), quantity_sold + VALUES(quantity_sold), quantity_sold),
                    amount_ht = IF(last_event_id < VALUES(last_event_id), amount_ht + VALUES(amount_ht), amount_ht),
                    updated_at = IF(last_event_id < VALUES(last_event_id), NOW(), updated_at),
                    last_event_id = IF(last_event_id < VALUES(last_event_id), VALUES(last_event_id), last_event_id)
            ", [$entrepriseId, $date, $articleId, $qty, $amountHt, $event->id]);
        }
    }

    public function handleStopVisited(array $payload, DomainOutbox $event): void
    {
        $entrepriseId = $payload['entrepriseId'];
        $date = Carbon::parse($payload['visitedAt'] ?? now())->toDateString();
        $routeId = $payload['routeId'] ?? 0;

        DB::statement("
            INSERT INTO dashboard_sales (entreprise_id, date, route_id, nb_clients_visited, last_event_id, created_at, updated_at)
            VALUES (?, ?, ?, 1, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                nb_clients_visited = IF(last_event_id < VALUES(last_event_id), nb_clients_visited + 1, nb_clients_visited),
                updated_at = IF(last_event_id < VALUES(last_event_id), NOW(), updated_at),
                last_event_id = IF(last_event_id < VALUES(last_event_id), VALUES(last_event_id), last_event_id)
        ", [$entrepriseId, $date, $routeId, $event->id]);
    }
}
