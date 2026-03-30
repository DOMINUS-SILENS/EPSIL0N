<?php

namespace App\Services;

use App\Aggregates\OrderAggregate;
use App\Models\Order;
use App\Models\OrderLine;
use Illuminate\Support\Facades\DB;
use App\Services\ApiIdempotencyService;

class OrderApplicationService
{
    public function __construct(
        protected ApiIdempotencyService $idempotency
    ) {}

    public function createOrder(array $payload, string $userId, string $endpoint, string $clientMutationId, string $deviceId): array
    {
        $payloadHash = $this->idempotency->hashPayload($payload);
        
        // 1. Idempotency Lock
        $idempotencyRecord = $this->idempotency->acquire($endpoint, $clientMutationId, $deviceId, $payloadHash);
        
        // Handle Idempotency Scenarios
        if (isset($idempotencyRecord->id)) {
            // It already existed
            if ($idempotencyRecord->payload_hash !== $payloadHash) {
                // Same client_mutation_id but different payload -> 409 Conflict
                abort(409, 'Conflict: Mutation ID reused with different payload.');
            }
            
            if ($idempotencyRecord->status === 'completed') {
                return json_decode($idempotencyRecord->response_body, true);
            }
            
            if ($idempotencyRecord->status === 'processing') {
                abort(429, 'Conflict: Mutation is currently processing.');
            }
        }

        try {
            // Transaction Boundary for Write Flow
            $result = DB::transaction(function () use ($payload, $userId) {
                $id = (string) \Illuminate\Support\Str::uuid();
                $reference = 'CMD-' . date('Y') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
                
                // 3. Append event_store + insert domain_outbox (handled internally by Aggregate + OutboxService wrapper)
                OrderAggregate::retrieve($id)
                    ->createOrder(array_merge($payload, ['reference' => $reference]))
                    ->persist();

                // 4. Update Business State (Launch Bridge / Read Models) inside SAME transaction
                \App\Models\Order::create([
                    'id' => $id,
                    'entreprise_id' => $payload['entreprise_id'] ?? 1,
                    'reference' => $reference,
                    'customer_id' => $payload['customer_id'] ?? 1,
                    'customer_name' => $payload['customer_name'] ?? 'Inconnu',
                    'status' => 'submitted',
                    'subtotal_amount' => $payload['subtotal_amount'] ?? 0,
                    'total_amount' => $payload['total_amount'] ?? 0,
                    'created_by' => $userId,
                ]);

                if (!empty($payload['lines'])) {
                    foreach ($payload['lines'] as $line) {
                        \App\Models\OrderLine::create([
                            'order_id' => $id,
                            'entreprise_id' => $payload['entreprise_id'] ?? 1,
                            'product_id' => $line['product_id'],
                            'product_name' => $line['product_name'] ?? 'Article',
                            'quantity' => $line['quantity'],
                            'unit_price' => $line['unit_price'],
                            'total' => ($line['quantity'] * $line['unit_price']),
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'order_id' => $id,
                    'reference' => $reference,
                ];
            });
            
            // Mark Completed Handled outside the DB transaction to ensure it occurs after commit
            $this->idempotency->complete($idempotencyRecord->id ?? $idempotencyRecord, 201, $result, $result['order_id']);

            return $result;

        } catch (\Throwable $e) {
            $this->idempotency->fail($idempotencyRecord->id ?? $idempotencyRecord, 500, ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
