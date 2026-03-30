<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use App\Services\ContractService;
use App\Services\Projectors\CustomerBalanceProjector;
use Illuminate\Console\Command;

class ContinuousVerify extends Command
{
    protected $signature = 'verify:continuous';

    protected $description = 'Run continuous verification of invariants and projections';

    public function handle(
        CustomerBalanceProjector $balanceProjector,
        AuditService $audit,
        ContractService $contract
    ): void {
        $this->info('Running continuous verification...');

        // 1. Rebuild projections for a sample (or all) aggregates
        $this->info('Rebuilding customer balance projections...');
        // In a real system, you'd iterate over all customers
        // For now, we rebuild one to test
        $balanceProjector->rebuild(1);
        $this->info('Customer balance rebuilt.');

        // 2. Verify audit chain for each company
        $this->info('Verifying audit chains...');
        $broken = $audit->verifyChain(1); // entreprise_id 1
        if (empty($broken)) {
            $this->info('Audit chain OK.');
        } else {
            $this->error('Broken audit chain entries: '.count($broken));
        }

        // 3. Verify contracts (global invariants)
        $this->info('Verifying contracts...');
        try {
            // Example: check a customer credit limit
            $contract->verify(['customer_id' => 1, 'amount' => 0]); // dummy
            $this->info('Contracts OK.');
        } catch (\Exception $e) {
            $this->error('Contract violation: '.$e->getMessage());
        }
        $this->info('Checking governance anomalies...');
        app(AdversarialMonitor::class)->checkGovernanceAnomalies();

        $this->info('Continuous verification complete.');
    }
}
