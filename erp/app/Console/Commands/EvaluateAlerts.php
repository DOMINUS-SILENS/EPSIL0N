<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AlertingService;

/**
 * Command to evaluate alert rules and trigger notifications.
 * Should run on a schedule (every minute or every 5 minutes).
 */
class EvaluateAlerts extends Command
{
    protected $signature = 'alerts:evaluate {--company= : Specific company ID to evaluate}';

    protected $description = 'Evaluate alert rules and trigger notifications';

    public function handle(AlertingService $service): int
    {
        $entrepriseId = $this->option('company');

        if ($entrepriseId) {
            $this->info("Evaluating alerts for company {$entrepriseId}...");
            $triggered = $service->evaluateRules($entrepriseId);
        } else {
            $this->info('Evaluating alerts for all companies...');
            // Get all companies with active rules
            $companies = \App\Models\AlertRule::active()
                ->distinct('entreprise_id')
                ->pluck('entreprise_id');

            $triggered = [];
            foreach ($companies as $id) {
                $triggered = array_merge($triggered, $service->evaluateRules($id));
            }
        }

        $this->info(count($triggered) . ' alert(s) triggered.');

        foreach ($triggered as $alert) {
            $this->line("- [{$alert->severity}] {$alert->name}");
        }

        return Command::SUCCESS;
    }
}
