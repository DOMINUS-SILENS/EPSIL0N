<?php

namespace App\Services\Projectors;

use App\Services\Projector;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class PurchasingProjector extends Projector
{
    public function handlePurchaseOrderCreated(array $payload, DomainOutbox $event): void
    {
        DB::table('purchase_orders')->insert([
            'entreprise_id' => $payload['entrepriseId'],
            'purchase_order_id' => $payload['purchaseOrderId'],
            'supplier_id' => $payload['supplierId'],
            'order_date' => now()->toDateString(),
            'total_amount' => $payload['totalAmount'],
            'status' => 'draft',
            'last_event_id' => $event->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($payload['items'] as $item) {
            DB::table('purchase_order_items')->insert([
                'entreprise_id' => $payload['entrepriseId'],
                'purchase_order_id' => $payload['purchaseOrderId'],
                'article_id' => $item['article_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'last_event_id' => $event->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
