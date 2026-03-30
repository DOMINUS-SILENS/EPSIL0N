<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\DomainOutbox;

class PrometheusController extends Controller
{
    /**
     * Expose metrics in Prometheus text format.
     */
    public function metrics()
    {
        $metrics = [];

        // 1. Outbox Lag
        $outboxLag = DomainOutbox::where('status', 'pending')->count();
        $metrics[] = "# HELP epsilon_outbox_lag_total Number of pending events in outbox";
        $metrics[] = "# TYPE epsilon_outbox_lag_total gauge";
        $metrics[] = "epsilon_outbox_lag_total {$outboxLag}";

        // 2. Dead Letter Queue Size
        $dlqSize = DB::table('dead_letters')->count() + DB::table('failed_outbox_events')->count();
        $metrics[] = "# HELP epsilon_dlq_size_total Number of permanently failed events";
        $metrics[] = "# TYPE epsilon_dlq_size_total gauge";
        $metrics[] = "epsilon_dlq_size_total {$dlqSize}";

       // 3. Snapshot Frequency
        $snapshotCount = DB::table('aggregate_snapshots')->count();
        $metrics[] = "# HELP epsilon_snapshots_total Total number of aggregate snapshots stored";
        $metrics[] = "# TYPE epsilon_snapshots_total counter";
        $metrics[] = "epsilon_snapshots_total {$snapshotCount}";

        // 4. Shard Health (Active shards)
        $shardCount = DB::table('event_store')->distinct('shard_id')->count();
        $metrics[] = "# HELP epsilon_event_store_shards_active Number of active event store shards";
        $metrics[] = "# TYPE epsilon_event_store_shards_active gauge";
        $metrics[] = "epsilon_event_store_shards_active {$shardCount}";

        return response(implode("\n", $metrics) . "\n", 200)
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }
}
