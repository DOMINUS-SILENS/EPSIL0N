<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Alert Rule for Business Metrics Monitoring.
 */
class AlertRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'enabled' => 'boolean',
        'last_triggered_at' => 'datetime',
        'cooldown_minutes' => 'integer',
    ];

    /**
     * Check if alert is in cooldown period.
     */
    public function isInCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->diffInMinutes(now()) < $this->cooldown_minutes;
    }

    /**
     * Get metric source table based on metric type.
     */
    public function getMetricSource(): string
    {
        return match ($this->metric_type) {
            'stock_balance' => 'stock_balances',
            'credit_limit' => 'contacts',
            'sales_rate' => 'dashboard_sales',
            'delivery_rate' => 'missions',
            'custom' => $this->custom_source,
            default => throw new \InvalidArgumentException("Unknown metric type: {$this->metric_type}"),
        };
    }

    /**
     * Scope to find active rules.
     */
    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope to find rules for specific company.
     */
    public function scopeForCompany($query, int $entrepriseId)
    {
        return $query->where('entreprise_id', $entrepriseId);
    }

    /**
     * Scope to find rules needing evaluation (not in cooldown).
     */
    public function scopeReadyToEvaluate($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('last_triggered_at')
              ->orWhereRaw(
                  "last_triggered_at <= DATE_SUB(NOW(), INTERVAL cooldown_minutes MINUTE)"
              );
        });
    }
}