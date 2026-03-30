<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerifySystem extends Command
{
    protected $signature = 'verify:system {--fix : Attempt to fix inconsistencies}';

    protected $description = 'Run independent verification of system invariants';

    public function handle(AuditService $audit, StockService $stock)
    {
        $this->info('Starting system verification...');

        // 1. Verify audit hash chain
        $this->verifyAuditChain($audit);

        // 2. Verify stock_quants vs moves
        $this->verifyStock();

        // 3. Verify projection consistency (example: customer balance)
        $this->verifyProjections();

        // 4. Verify global invariants (credit limits, etc.)
        $this->verifyGlobalInvariants();

        $this->info('Verification completed.');
    }

    protected function verifyAuditChain(AuditService $audit)
    {
        $this->info('Verifying audit chain...');
        $broken = $audit->verifyChain(1); // pass entreprise_id
        if (empty($broken)) {
            $this->info('Audit chain OK.');
        } else {
            $this->error('Broken audit chain entries: '.count($broken));
            Log::error('Broken audit chain', ['entries' => $broken]);
            // Optionally fix: could rebuild chain (but only if tampering is detected)
        }
    }

    protected function verifyStock()
    {
        $this->info('Verifying stock_quants vs stock_moves...');
        $inconsistencies = DB::select("
            SELECT sq.product_id, sq.warehouse_id, sq.qty as quant_qty,
                   COALESCE(SUM(CASE WHEN sm.type IN ('in','adjustment_in') THEN sm.qty WHEN sm.type IN ('out','adjustment_out') THEN -sm.qty END), 0) as moves_sum
            FROM stock_quants sq
            LEFT JOIN stock_moves sm ON sm.product_id = sq.product_id AND sm.warehouse_id = sq.warehouse_id
            GROUP BY sq.product_id, sq.warehouse_id, sq.qty
            HAVING ABS(sq.qty - moves_sum) > 0.0001
        ");
        if (empty($inconsistencies)) {
            $this->info('Stock OK.');
        } else {
            $this->error('Stock inconsistencies found: '.count($inconsistencies));
            Log::error('Stock inconsistencies', ['inconsistencies' => $inconsistencies]);
        }
    }

    protected function verifyProjections()
    {
        // Example: customer balance projection vs ledger
        $this->info('Verifying customer balances...');
        $inconsistencies = DB::select('
            SELECT cb.customer_id, cb.balance as projected,
                   COALESCE(SUM(jl.debit - jl.credit), 0) as ledger_balance
            FROM customer_balance_projections cb
            LEFT JOIN journal_lines jl ON jl.customer_id = cb.customer_id
            GROUP BY cb.customer_id, cb.balance
            HAVING ABS(cb.balance - ledger_balance) > 0.0001
        ');
        if (empty($inconsistencies)) {
            $this->info('Customer balances OK.');
        } else {
            $this->error('Balance inconsistencies found: '.count($inconsistencies));
            Log::error('Balance inconsistencies', ['inconsistencies' => $inconsistencies]);
            if ($this->option('fix')) {
                $this->call('projection:rebuild', ['customer_id' => null]); // rebuild all
            }
        }
    }

    protected function verifyGlobalInvariants()
    {
        // Example: credit limit exceeded
        $this->info('Verifying credit limits...');
        $exceeded = DB::select("
            SELECT c.id, c.credit_limit,
                   COALESCE(SUM(jl.debit - jl.credit), 0) + COALESCE(SUM(cr.amount), 0) as used
            FROM customers c
            LEFT JOIN journal_lines jl ON jl.customer_id = c.id
            LEFT JOIN credit_reservations cr ON cr.customer_id = c.id AND cr.status IN ('pending','confirmed')
            GROUP BY c.id, c.credit_limit
            HAVING used > c.credit_limit
        ");
        if (empty($exceeded)) {
            $this->info('Credit limits OK.');
        } else {
            $this->error('Credit limit exceeded for customers: '.collect($exceeded)->pluck('id')->implode(','));
            Log::error('Credit limit exceeded', ['customers' => $exceeded]);
        }
    }
}
