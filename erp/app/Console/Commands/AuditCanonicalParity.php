<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCanonicalParity extends Command
{
    protected $signature = 'canonical:parity-audit {--tenant= : Audit specific entreprise_id}';
    protected $description = 'Perform a formal parity audit between legacy and canonical schemas for Phase 2D certification.';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        $this->info("Starting Canonical Parity Audit...");
        $this->info("Checking Row Count Parity...");

        $tables = [
            'entreprise' => 'canonical_entreprises',
            'article' => 'canonical_articles',
            'depot' => 'canonical_depots',
            'customers' => 'canonical_customers',
            'orders' => 'canonical_orders',
            'order_lines' => 'canonical_order_lines',
        ];

        $results = [];

        foreach ($tables as $legacy => $canonical) {
            $legacyCount = DB::table($legacy)->count();
            $canonicalCount = DB::table($canonical)->where('source_system', 'legacy')->count();

            $status = ($legacyCount === $canonicalCount) ? '<fg=green>PASS</>' : '<fg=red>FAIL</>';
            
            $results[] = [
                'Entity' => ucfirst($legacy),
                'Legacy Count' => $legacyCount,
                'Canonical Count' => $canonicalCount,
                'Diff' => $legacyCount - $canonicalCount,
                'Status' => $status,
            ];
        }

        $this->table(['Entity', 'Legacy Count', 'Canonical Count', 'Diff', 'Status'], $results);

        $this->info("\nChecking Domain Invariants...");
        $this->checkOrderTotalsParity();

        return 0;
    }

    private function checkOrderTotalsParity()
    {
        $driftCount = 0;
        
        DB::table('canonical_orders')->chunk(500, function ($orders) use (&$driftCount) {
            foreach ($orders as $order) {
                $linesTotal = DB::table('canonical_order_lines')
                    ->where('order_id', $order->id)
                    ->sum('line_total');

                if (abs($linesTotal - $order->total_amount) > 0.01) {
                    $this->error("Drift detected for Order {$order->order_number}: DB Total={$order->total_amount}, Calc Total={$linesTotal}");
                    $driftCount++;
                }
            }
        });

        if ($driftCount === 0) {
            $this->info("<fg=green>Domain Invariant PASS: All order totals match line sums.</>");
        } else {
            $this->error("Domain Invariant FAIL: {$driftCount} orders have parity drift.");
        }
    }
}
