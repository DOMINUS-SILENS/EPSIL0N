<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Phase 2B-2: The Reconciliation Audit (Hardened v10)
 */
class ReconcileCanonicalData extends Command
{
    protected $signature = 'canonical:reconcile';
    protected $description = 'Generate a chiffré reconciliation report between legacy and canonical tables.';

    public function handle()
    {
        $this->info("Generating Reconciliation Report (v10)...");

        $report = "# Canonical Reconciliation Report (HARDENED v10)\n\n";
        $report .= "Generated At: " . now()->toDateTimeString() . "\n\n";

        $tableMapping = [
            'entreprises' => ['legacy' => 'entreprise'],
            'articles' => ['legacy' => 'article'],
            'depots' => ['legacy' => 'depot'],
            'customers' => ['legacy' => 'customers'],
            'orders' => ['legacy' => 'orders'],
            'order_lines' => ['legacy' => 'order_lines'],
            'stock_balances' => ['legacy' => 'balance_stock'],
            'device_sync_states' => ['legacy' => 'device_sync_state'],
        ];

        $report .= "## 1. Row Count Parity Proof\n\n";
        $report .= "| Table | Legacy | Canonical | Status |\n";
        $report .= "| :--- | :--- | :--- | :--- |\n";

        foreach ($tableMapping as $canonical => $meta) {
            $legacyCount = DB::table($meta['legacy'])->count();
            $canonicalCount = DB::table("canonical_{$canonical}")->count();
            $status = ($legacyCount === $canonicalCount) ? "✅ OK" : "❌ DISCREPANCY";
            
            $report .= "| `{$canonical}` | {$legacyCount} | {$canonicalCount} | {$status} |\n";
        }

        $report .= "\n## 2. Financial & Integrity Parity\n\n";

        // Articles Stock Quantity Parity
        $legacyArticleStock = DB::table('article')->sum('quantite_stock');
        $canonicalArticleStock = DB::table('canonical_articles')->sum('stock_quantity');
        $articleStockStatus = (abs($legacyArticleStock - $canonicalArticleStock) < 0.001) ? "✅ OK" : "❌ DISCREPANCY";
        $report .= "- **SUM(Articles Stock)**: Legacy: {$legacyArticleStock} | Canonical: {$canonicalArticleStock} | Status: {$articleStockStatus}\n";

        // Stock Balances Parity
        $legacyBalanceStock = DB::table('balance_stock')->sum('quantite_theorique'); // Legacy use theorique
        $canonicalBalanceStock = DB::table('canonical_stock_balances')->sum('available_quantity');
        $balanceStockStatus = (abs($legacyBalanceStock - $canonicalBalanceStock) < 0.001) ? "✅ OK" : "❌ DISCREPANCY";
        $report .= "- **SUM(Stock Balances)**: Legacy: {$legacyBalanceStock} | Canonical: {$canonicalBalanceStock} | Status: {$balanceStockStatus}\n";

        // Orders Total Amount
        $legacyOrders = DB::table('orders')->sum('total_amount');
        $canonicalOrders = DB::table('canonical_orders')->sum('total_amount');
        $orderStatus = (abs($legacyOrders - $canonicalOrders) < 0.01) ? "✅ OK" : "❌ DISCREPANCY";
        $report .= "- **SUM(Total Orders)**: Legacy: {$legacyOrders} | Canonical: {$canonicalOrders} | Status: {$orderStatus}\n";

        // Order Lines Parity
        $legacyLines = DB::table('order_lines')->sum('total');
        $canonicalLines = DB::table('canonical_order_lines')->sum('line_total');
        $lineStatus = (abs($legacyLines - $canonicalLines) < 0.01) ? "✅ OK" : "❌ DISCREPANCY";
        $report .= "- **SUM(Order Lines)**: Legacy: {$legacyLines} | Canonical: {$canonicalLines} | Status: {$lineStatus}\n";

        // Write the report to a file
        $reportPath = base_path('docs/certification/schema/reconciliation-report.md');
        File::ensureDirectoryExists(dirname($reportPath));
        File::put($reportPath, $report);

        $this->info("Report generated to {$reportPath}");

        return 0;
    }
}
