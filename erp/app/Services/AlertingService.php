<?php

namespace App\Services;

use App\Models\AlertRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Business Metrics Alerting Service.
 * Monitors projections and triggers alerts based on threshold conditions.
 */
class AlertingService
{
    public function __construct(
        protected MetricsService $metricsService,
        protected AuditService $auditService,
    ) {}

    /**
     * Evaluate all active alert rules for a company.
     */
    public function evaluateRules(int $entrepriseId): array
    {
        $rules = AlertRule::active()
            ->forCompany($entrepriseId)
            ->readyToEvaluate()
            ->get();

        $triggered = [];

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule)) {
                $this->triggerAlert($rule);
                $triggered[] = $rule;
            }
        }

        return $triggered;
    }

    /**
     * Evaluate a single rule condition.
     */
    public function evaluateRule(AlertRule $rule): bool
    {
        try {
            $conditions = $rule->conditions;
            $source = $rule->getMetricSource();

            $query = DB::table($source)
                ->where('entreprise_id', $rule->entreprise_id);

            // Apply time window if specified
            if (!empty($conditions['time_window_hours'])) {
                $query->where('created_at', '>=', now()->subHours($conditions['time_window_hours']));
            }

            // Build metric query based on aggregation type
            $metricValue = match ($conditions['aggregation'] ?? 'count') {
                'count' => $query->count(),
                'sum' => $query->sum($conditions['column'] ?? 'id'),
                'avg' => $query->avg($conditions['column'] ?? 'id'),
                'min' => $query->min($conditions['column'] ?? 'id'),
                'max' => $query->max($conditions['column'] ?? 'id'),
                'custom' => $this->evaluateCustomCondition($rule),
                default => $query->count(),
            };

            if ($metricValue === null) {
                return false;
            }

            // Compare against threshold
            $threshold = $conditions['threshold'] ?? 0;
            $operator = $conditions['operator'] ?? '>';

            $triggered = match ($operator) {
                '>' => $metricValue > $threshold,
                '>=' => $metricValue >= $threshold,
                '<' => $metricValue < $threshold,
                '<=' => $metricValue <= $threshold,
                '==' => $metricValue == $threshold,
                '!=' => $metricValue != $threshold,
                default => false,
            };

            Log::debug('Alert rule evaluated', [
                'rule_id' => $rule->id,
                'metric_value' => $metricValue,
                'threshold' => $threshold,
                'operator' => $operator,
                'triggered' => $triggered,
            ]);

            return $triggered;

        } catch (\Exception $e) {
            Log::error('Failed to evaluate alert rule', [
                'rule_id' => $rule->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Trigger alert actions.
     */
    protected function triggerAlert(AlertRule $rule): void
    {
        $actions = $rule->actions ?? [];

        foreach ($actions as $action) {
            match ($action['type'] ?? 'log') {
                'log' => $this->sendLogAlert($rule, $action),
                'webhook' => $this->sendWebhookAlert($rule, $action),
                'email' => $this->sendEmailAlert($rule, $action),
                'sms' => $this->sendSmsAlert($rule, $action),
                'slack' => $this->sendSlackAlert($rule, $action),
                default => Log::warning("Unknown alert action type: {$action['type']}"),
            };
        }

        // Update last triggered
        $rule->update([
            'last_triggered_at' => now(),
            'trigger_count' => DB::raw('trigger_count + 1'),
        ]);

        // Audit trail
        $this->auditService->logEvent('alert_triggered', [
            'rule_id' => $rule->id,
            'rule_name' => $rule->name,
            'entreprise_id' => $rule->entreprise_id,
        ]);
    }

    /**
     * Evaluate custom conditions for complex rules.
     */
    protected function evaluateCustomCondition(AlertRule $rule): ?float
    {
        return match ($rule->metric_type) {
            'stock_rupture' => $this->calculateStockRuptureRisk($rule->entreprise_id),
            'credit_limit_breach' => $this->calculateCreditLimitBreach($rule->entreprise_id),
            'validation_rate' => $this->calculateValidationRate($rule->entreprise_id, $rule->conditions['time_window_hours'] ?? 24),
            default => null,
        };
    }

    /**
     * Calculate stock rupture risk percentage.
     */
    protected function calculateStockRuptureRisk(int $entrepriseId): float
    {
        $total = DB::table('stock_balances')
            ->where('entreprise_id', $entrepriseId)
            ->count();

        if ($total === 0) return 0;

        $atRisk = DB::table('stock_balances')
            ->where('entreprise_id', $entrepriseId)
            ->whereRaw('quantity <= ?', [10]) // configurable threshold
            ->count();

        return ($atRisk / $total) * 100;
    }

    /**
     * Calculate credit limit breach count.
     */
    protected function calculateCreditLimitBreach(int $entrepriseId): int
    {
        return DB::table('contacts')
            ->where('entreprise_id', $entrepriseId)
            ->whereRaw('montant_credit_en_cours > montant_max_credit')
            ->where('montant_max_credit', '>', 0)
            ->count();
    }

    /**
     * Calculate validation rate percentage.
     */
    protected function calculateValidationRate(int $entrepriseId, int $hours): float
    {
        $total = DB::table('mouvements')
            ->where('entreprise_id', $entrepriseId)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        if ($total === 0) return 0;

        $validated = DB::table('mouvements')
            ->where('entreprise_id', $entrepriseId)
            ->where('status', 'validated')
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        return ($validated / $total) * 100;
    }

    // Alert action implementations
    protected function sendLogAlert(AlertRule $rule, array $action): void
    {
        Log::warning("[ALERT] {$rule->name}: {$rule->description}", [
            'rule_id' => $rule->id,
            'entreprise_id' => $rule->entreprise_id,
            'severity' => $action['severity'] ?? 'warning',
        ]);
    }

    protected function sendWebhookAlert(AlertRule $rule, array $action): void
    {
        $payload = [
            'alert_name' => $rule->name,
            'description' => $rule->description,
            'timestamp' => now()->toIso8601String(),
            'entreprise_id' => $rule->entreprise_id,
            'severity' => $action['severity'] ?? 'warning',
        ];

        // Async dispatch
        dispatch(function () use ($action, $payload) {
            try {
                \Http::timeout(10)
                    ->withHeaders(['X-Alert-Signature' => hash_hmac('sha256', json_encode($payload), $action['secret'] ?? '')])
                    ->post($action['url'], $payload);
            } catch (\Exception $e) {
                Log::error('Webhook alert failed', ['url' => $action['url'], 'error' => $e->getMessage()]);
            }
        });
    }

    protected function sendEmailAlert(AlertRule $rule, array $action): void
    {
        // Implementation depends on your mail setup
        // Notification::route('mail', $action['recipients'])
        //     ->notify(new AlertNotification($rule));
    }

    protected function sendSmsAlert(AlertRule $rule, array $action): void
    {
        // Implementation depends on SMS provider (Twilio, etc.)
    }

    protected function sendSlackAlert(AlertRule $rule, array $action): void
    {
        // Implementation depends on Slack Webhook
    }
}