<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApiIdempotencyService
{
    /**
     * Tries to acquire a lock for processing an idempotent request.
     * Returns the current record or creates a new one in 'processing' state.
     */
    public function acquire(string $endpoint, string $clientMutationId, ?string $deviceId, string $payloadHash): object|null
    {
        return DB::transaction(function () use ($endpoint, $clientMutationId, $deviceId, $payloadHash) {
            // Lock the record if it exists
            $record = DB::table('api_idempotency_keys')
                ->where('endpoint', $endpoint)
                ->where('client_mutation_id', $clientMutationId)
                ->lockForUpdate()
                ->first();

            if ($record) {
                return $record;
            }

            // Create new record in 'processing' state
            $id = DB::table('api_idempotency_keys')->insertGetId([
                'endpoint' => $endpoint,
                'client_mutation_id' => $clientMutationId,
                'device_id' => $deviceId,
                'payload_hash' => $payloadHash,
                'status' => 'processing',
                'locked_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return DB::table('api_idempotency_keys')->find($id);
        });
    }

    /**
     * Marks an idempotency key as completed and stores the canonical response.
     */
    public function complete(int $id, int $responseCode, array $responseBody, ?string $aggregateId = null): void
    {
        DB::table('api_idempotency_keys')->where('id', $id)->update([
            'status' => 'completed',
            'response_code' => $responseCode,
            'response_body' => json_encode($responseBody),
            'aggregate_id' => $aggregateId,
            'completed_at' => Carbon::now(),
            'locked_at' => null,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Marks an idempotency key as failed. 
     * This allows it to be legitimately retried (depending on policy).
     */
    public function fail(int $id, int $responseCode, array $responseBody): void
    {
        DB::table('api_idempotency_keys')->where('id', $id)->update([
            'status' => 'failed',
            'response_code' => $responseCode,
            'response_body' => json_encode($responseBody),
            'failed_at' => Carbon::now(),
            'locked_at' => null,
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Hash the incoming request payload predictably.
     */
    public function hashPayload(array $payload): string
    {
        // Sort keys recursively to ensure consistent hashing
        $this->recursiveKsort($payload);
        return hash('sha256', json_encode($payload));
    }

    private function recursiveKsort(array &$array): void
    {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->recursiveKsort($value);
            }
        }
    }
}
