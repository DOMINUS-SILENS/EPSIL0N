<?php

namespace App\Http\Controllers\Api;

use App\Models\Anomaly;
use App\Services\SystemModeService;
use Illuminate\Support\Facades\Queue;

class HealthController
{
    public function dashboard()
    {
        return response()->json([
            'system' => [
                'mode' => app(SystemModeService::class)->getCurrentMode(),
                'queue_depth' => Queue::size('default'),
                'last_anomalies' => Anomaly::latest()->limit(5)->get(),
            ],
            'event_sourcing' => [
                'outbox_lag' => \App\Models\DomainOutbox::where('status', 'pending')->count(),
                'dead_letters' => \Illuminate\Support\Facades\DB::table('dead_letters')->count(),
                'failed_outbox' => \Illuminate\Support\Facades\DB::table('failed_outbox_events')->count(),
                'snapshot_count' => \Illuminate\Support\Facades\DB::table('aggregate_snapshots')->count(),
                'shards_health' => \Illuminate\Support\Facades\DB::table('event_store')->distinct('shard_id')->count(),
            ],
            'latency_benchmarks' => $this->getLatencyMetrics(),
        ]);
    }

    protected function getLatencyMetrics(): array
    {
        // Placeholder for real-time latency tracking (e.g. from Redis or specialized log)
        return [
            'avg_projection_delay_ms' => 45,
            'avg_sequence_allocation_ms' => 2,
        ];
    }

    protected function getFailureRate(): float
    {
        return 0.02; // Mock
    }
}
