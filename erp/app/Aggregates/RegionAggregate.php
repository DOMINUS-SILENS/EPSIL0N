<?php

declare(strict_types=1);

namespace App\Aggregates;

use App\Events\Region\RegionCoveragePlanGenerated;
use App\Events\Region\RegionCoveragePlanUpdated;
use App\Events\Region\ZoneQuotaAdjusted;
use App\Events\Region\ZoneQuotaRemoved;
use App\Events\Region\PricingPolicyChanged;
use App\Events\Region\DiscountRuleAdded;
use App\Events\Region\DiscountRuleRemoved;
use App\Events\Region\TournéeFrequencyRuleChanged;
use App\Events\Region\TournéePlannedForZone;
use App\Events\Region\TournéeCancelledForZone;
use App\Events\Region\ZoneRouteAccepted;
use App\Events\Region\ZoneRouteNeedsAdaptation;
use App\Events\Region\ZoneCoverageCompromised;
use App\Events\Region\ZoneStockLocked;
use App\Events\Region\ZoneCreditLocked;
use App\Events\Region\StockReleased;
use App\Events\Region\CreditReleased;

/**
 * Region Aggregate - Policy + Capacity Orchestration
 * 
 * Invariants:
 * - Coverage plan must include all active Zones
 * - Zone daily load cannot exceed max capacity
 * - Pricing/discount policies must be consistent
 * - Tournée frequency rules enforced
 * - Stock/credit locked when Zone accepts route
 */
class RegionAggregate extends AggregateRoot
{
    private string $regionId;
    private ?string $name = null;
    private array $coveragePlan = []; // zone_id => [frequency, max_daily_load, priority]
    private array $zoneQuotas = []; // zone_id => [daily_capacity, current_load, utilization_pct]
    private array $pricingPolicies = []; // policy_id => [type, rules, effective_date, end_date]
    private array $discountRules = []; // rule_id => [conditions, discount_pct, priority]
    private array $tournéeFrequencyRules = []; // zone_id => [min_days_between, max_per_week, preferred_days]
    private array $activeTournées = []; // tournée_id => [zone_id, date, status, route_id, stock_locked, credit_locked]
    private array $pendingAdaptations = []; // adaptation_id => [zone_id, reason, requested_at]
    private array $lockedStock = []; // tournée_id => [product_id => quantity]
    private array $lockedCredit = []; // tournée_id => [customer_id => amount]
    
    // Quarantine tracking
    private int $consecutiveRejectionThreshold = 3;
    private array $zoneRejectionCounts = []; // zone_id => count
    private array $quarantinedZones = []; // zone_id => [reason, quarantined_at]

    protected function __construct(string $uuid)
    {
        parent::__construct($uuid);
        $this->regionId = $uuid;
    }

    /**
     * Generate coverage plan for all Zones
     * Invariant: All active zones must be included
     */
    public function generateCoveragePlan(
        string $planId,
        array $zoneConfigs, // [zone_id => [frequency, max_daily_load, priority]]
        ?string $effectiveDate = null,
        ?string $notes = null
    ): void {
        // Invariant: Cannot have duplicate zones
        if (count($zoneConfigs) !== count(array_unique(array_keys($zoneConfigs)))) {
            throw new \InvalidArgumentException('Duplicate zones in coverage plan');
        }

        // Invariant: All zones must have valid capacity
        foreach ($zoneConfigs as $zoneId => $config) {
            if ($config['max_daily_load'] <= 0) {
                throw new \InvalidArgumentException("Zone {$zoneId} must have positive max_daily_load");
            }
            if ($config['frequency'] < 1) {
                throw new \InvalidArgumentException("Zone {$zoneId} frequency must be at least 1 visit");
            }
        }

        $this->recordThat(new RegionCoveragePlanGenerated(
            $this->regionId,
            $planId,
            $zoneConfigs,
            $effectiveDate ?? now()->toDateString(),
            $notes
        ));
    }

    /**
     * Update existing coverage plan
     */
    public function updateCoveragePlan(
        string $planId,
        array $zoneConfigs,
        ?string $notes = null
    ): void {
        $this->recordThat(new RegionCoveragePlanUpdated(
            $this->regionId,
            $planId,
            $zoneConfigs,
            $notes
        ));
    }

    /**
     * Adjust quota for specific Zone
     * Invariant: New quota must not exceed max capacity from coverage plan
     */
    public function adjustZoneQuota(
        string $zoneId,
        int $newDailyCapacity,
        string $reason,
        ?string $effectiveFrom = null
    ): void {
        // Invariant: Cannot adjust quarantined zone without manual override
        if (isset($this->quarantinedZones[$zoneId])) {
            throw new \DomainException("Zone {$zoneId} is quarantined. Manual override required.");
        }

        // Invariant: New capacity must be positive
        if ($newDailyCapacity <= 0) {
            throw new \InvalidArgumentException('Daily capacity must be positive');
        }

        // Invariant: If coverage plan exists, new capacity cannot exceed max_daily_load
        if (isset($this->coveragePlan[$zoneId])) {
            $maxLoad = $this->coveragePlan[$zoneId]['max_daily_load'];
            if ($newDailyCapacity > $maxLoad) {
                throw new \InvalidArgumentException(
                    "Zone {$zoneId} capacity {$newDailyCapacity} exceeds max load {$maxLoad}"
                );
            }
        }

        $this->recordThat(new ZoneQuotaAdjusted(
            $this->regionId,
            $zoneId,
            $newDailyCapacity,
            $reason,
            $effectiveFrom ?? now()->toDateString()
        ));
    }

    /**
     * Remove zone quota (e.g., zone deactivated)
     */
    public function removeZoneQuota(string $zoneId, string $reason): void
    {
        $this->recordThat(new ZoneQuotaRemoved(
            $this->regionId,
            $zoneId,
            $reason
        ));
    }

    /**
     * Change pricing policy
     */
    public function changePricingPolicy(
        string $policyId,
        string $type, // 'base', 'promotional', 'seasonal', 'volume'
        array $rules, // [product_category => [price, min_qty, max_qty]]
        string $effectiveDate,
        ?string $endDate = null,
        ?string $notes = null
    ): void {
        // Invariant: Date consistency
        if ($endDate && $effectiveDate >= $endDate) {
            throw new \InvalidArgumentException('End date must be after effective date');
        }

        $this->recordThat(new PricingPolicyChanged(
            $this->regionId,
            $policyId,
            $type,
            $rules,
            $effectiveDate,
            $endDate,
            $notes
        ));
    }

    /**
     * Add discount rule
     */
    public function addDiscountRule(
        string $ruleId,
        array $conditions, // [min_order_value, customer_segment, product_category, payment_method]
        float $discountPercentage,
        int $priority = 0,
        ?string $effectiveFrom = null,
        ?string $effectiveUntil = null
    ): void {
        // Invariant: Discount must be between 0 and 100
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100');
        }

        $this->recordThat(new DiscountRuleAdded(
            $this->regionId,
            $ruleId,
            $conditions,
            $discountPercentage,
            $priority,
            $effectiveFrom,
            $effectiveUntil
        ));
    }

    /**
     * Remove discount rule
     */
    public function removeDiscountRule(string $ruleId, string $reason): void
    {
        $this->recordThat(new DiscountRuleRemoved(
            $this->regionId,
            $ruleId,
            $reason
        ));
    }

    /**
     * Change tournée frequency rules for a zone
     */
    public function changeTournéeFrequencyRules(
        string $zoneId,
        int $minDaysBetween,
        int $maxPerWeek,
        array $preferredDays, // [1,3,5] for Mon,Wed,Fri
        ?string $notes = null
    ): void {
        // Invariant: Preferred days must be valid (1-7)
        foreach ($preferredDays as $day) {
            if ($day < 1 || $day > 7) {
                throw new \InvalidArgumentException('Preferred days must be 1-7 (Mon-Sun)');
            }
        }

        // Invariant: maxPerWeek cannot exceed 7
        if ($maxPerWeek > 7) {
            throw new \InvalidArgumentException('Max per week cannot exceed 7');
        }

        $this->recordThat(new TournéeFrequencyRuleChanged(
            $this->regionId,
            $zoneId,
            $minDaysBetween,
            $maxPerWeek,
            $preferredDays,
            $notes
        ));
    }

    /**
     * Plan a tournée for a zone (Region → Zone command)
     * This is the key orchestration event
     */
    public function planTournéeForZone(
        string $tournéeId,
        string $zoneId,
        string $date,
        string $routeId,
        string $pricingProfileId,
        float $targetVolume,
        array $customers, // [customer_id => priority]
        ?string $notes = null
    ): void {
        // Invariant: Zone must not be quarantined
        if (isset($this->quarantinedZones[$zoneId])) {
            throw new \DomainException("Cannot plan tournée for quarantined zone {$zoneId}");
        }

        // Invariant: Zone must have capacity available
        if (isset($this->zoneQuotas[$zoneId])) {
            $quota = $this->zoneQuotas[$zoneId];
            if ($quota['current_load'] + $targetVolume > $quota['daily_capacity']) {
                throw new \DomainException(
                    "Zone {$zoneId} capacity exceeded: current {$quota['current_load']} + target {$targetVolume} > capacity {$quota['daily_capacity']}"
                );
            }
        }

        // Invariant: Check tournée frequency rules
        if (isset($this->tournéeFrequencyRules[$zoneId])) {
            $rules = $this->tournéeFrequencyRules[$zoneId];
            $weekTournées = $this->countTournéesForZoneInWeek($zoneId, $date);
            if ($weekTournées >= $rules['max_per_week']) {
                throw new \DomainException(
                    "Zone {$zoneId} max tournées per week ({$rules['max_per_week']}) exceeded"
                );
            }
        }

        $this->recordThat(new TournéePlannedForZone(
            $this->regionId,
            $tournéeId,
            $zoneId,
            $date,
            $routeId,
            $pricingProfileId,
            $targetVolume,
            $customers,
            $notes
        ));
    }

    /**
     * Cancel planned tournée
     */
    public function cancelTournéeForZone(
        string $tournéeId,
        string $reason,
        bool $releaseLocks = true
    ): void {
        if (!isset($this->activeTournées[$tournéeId])) {
            throw new \DomainException("Tournée {$tournéeId} not found");
        }

        $this->recordThat(new TournéeCancelledForZone(
            $this->regionId,
            $tournéeId,
            $reason,
            $releaseLocks
        ));

        if ($releaseLocks) {
            $this->releaseTournéeLocks($tournéeId);
        }
    }

    /**
     * Handle Zone route acceptance (Zone → Region event)
     * Lock stock and credit
     */
    public function handleZoneRouteAccepted(
        string $tournéeId,
        string $zoneId,
        string $acceptedRouteId,
        array $stockRequirements, // [product_id => quantity]
        array $creditRequirements, // [customer_id => amount]
        ?string $acceptedAt = null
    ): void {
        // Invariant: Tournée must be in planned state
        if (!isset($this->activeTournées[$tournéeId]) || $this->activeTournées[$tournéeId]['status'] !== 'planned') {
            throw new \DomainException("Tournée {$tournéeId} not in planned state");
        }

        // Reset rejection count for zone (route was accepted)
        $this->zoneRejectionCounts[$zoneId] = 0;

        $this->recordThat(new ZoneRouteAccepted(
            $this->regionId,
            $tournéeId,
            $zoneId,
            $acceptedRouteId,
            $stockRequirements,
            $creditRequirements,
            $acceptedAt ?? now()->toDateTimeString()
        ));

        // Lock stock
        if (!empty($stockRequirements)) {
            $this->recordThat(new ZoneStockLocked(
                $this->regionId,
                $tournéeId,
                $zoneId,
                $stockRequirements
            ));
        }

        // Lock credit
        if (!empty($creditRequirements)) {
            $this->recordThat(new ZoneCreditLocked(
                $this->regionId,
                $tournéeId,
                $zoneId,
                $creditRequirements
            ));
        }
    }

    /**
     * Handle Zone route adaptation request (Zone → Region event)
     */
    public function handleZoneRouteNeedsAdaptation(
        string $adaptationId,
        string $tournéeId,
        string $zoneId,
        string $reason,
        array $requestedChanges, // [volume_adjustment, date_change, customer_exclusions]
        ?string $requestedAt = null
    ): void {
        // Track rejection for quarantine logic
        $this->zoneRejectionCounts[$zoneId] = ($this->zoneRejectionCounts[$zoneId] ?? 0) + 1;

        // Check quarantine threshold
        if ($this->zoneRejectionCounts[$zoneId] >= $this->consecutiveRejectionThreshold) {
            $this->quarantineZone($zoneId, 'CONSECUTIVE_REJECTIONS', 
                "Zone {$zoneId} rejected {$this->zoneRejectionCounts[$zoneId]} consecutive routes");
        }

        $this->recordThat(new ZoneRouteNeedsAdaptation(
            $this->regionId,
            $adaptationId,
            $tournéeId,
            $zoneId,
            $reason,
            $requestedChanges,
            $requestedAt ?? now()->toDateTimeString()
        ));
    }

    /**
     * Handle zone coverage compromised (emergency)
     */
    public function handleZoneCoverageCompromised(
        string $zoneId,
        string $reason,
        array $affectedCustomers,
        array $impactMetrics, // [missed_volume, missed_revenue, coverage_gap_days]
        ?string $detectedAt = null
    ): void {
        $this->recordThat(new ZoneCoverageCompromised(
            $this->regionId,
            $zoneId,
            $reason,
            $affectedCustomers,
            $impactMetrics,
            $detectedAt ?? now()->toDateTimeString()
        ));
    }

    /**
     * Release locks for cancelled/completed tournée
     */
    public function releaseTournéeLocks(string $tournéeId): void
    {
        if (isset($this->lockedStock[$tournéeId])) {
            $this->recordThat(new StockReleased(
                $this->regionId,
                $tournéeId,
                $this->lockedStock[$tournéeId]
            ));
        }

        if (isset($this->lockedCredit[$tournéeId])) {
            $this->recordThat(new CreditReleased(
                $this->regionId,
                $tournéeId,
                $this->lockedCredit[$tournéeId]
            ));
        }
    }

    /**
     * Manually quarantine a zone (admin override)
     */
    public function quarantineZone(
        string $zoneId,
        string $reason,
        ?string $details = null
    ): void {
        if (!isset($this->quarantinedZones[$zoneId])) {
            $this->quarantinedZones[$zoneId] = [
                'reason' => $reason,
                'details' => $details,
                'quarantined_at' => now()->toDateTimeString()
            ];
        }
    }

    /**
     * Remove zone from quarantine
     */
    public function unquarantineZone(string $zoneId, string $reason): void
    {
        unset($this->quarantinedZones[$zoneId]);
        $this->zoneRejectionCounts[$zoneId] = 0;
    }

    /**
     * Get zone utilization
     */
    public function getZoneUtilization(string $zoneId): ?float
    {
        if (!isset($this->zoneQuotas[$zoneId])) {
            return null;
        }
        $quota = $this->zoneQuotas[$zoneId];
        return $quota['daily_capacity'] > 0 
            ? ($quota['current_load'] / $quota['daily_capacity']) * 100 
            : 0;
    }

    /**
     * Count active tournées for zone in given week
     */
    private function countTournéesForZoneInWeek(string $zoneId, string $date): int
    {
        $weekStart = \Carbon\Carbon::parse($date)->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        $count = 0;
        foreach ($this->activeTournées as $tournée) {
            if ($tournée['zone_id'] === $zoneId && 
                $tournée['status'] === 'planned' &&
                $tournée['date'] >= $weekStart->toDateString() &&
                $tournée['date'] <= $weekEnd->toDateString()) {
                $count++;
            }
        }
        return $count;
    }

    // ========== Event Application Methods ==========

    protected function applyRegionCoveragePlanGenerated(RegionCoveragePlanGenerated $event): void
    {
        $this->coveragePlan = $event->zoneConfigs;
    }

    protected function applyRegionCoveragePlanUpdated(RegionCoveragePlanUpdated $event): void
    {
        $this->coveragePlan = $event->zoneConfigs;
    }

    protected function applyZoneQuotaAdjusted(ZoneQuotaAdjusted $event): void
    {
        $this->zoneQuotas[$event->zoneId] = [
            'daily_capacity' => $event->newDailyCapacity,
            'current_load' => $this->zoneQuotas[$event->zoneId]['current_load'] ?? 0,
            'utilization_pct' => 0
        ];
    }

    protected function applyZoneQuotaRemoved(ZoneQuotaRemoved $event): void
    {
        unset($this->zoneQuotas[$event->zoneId]);
    }

    protected function applyPricingPolicyChanged(PricingPolicyChanged $event): void
    {
        $this->pricingPolicies[$event->policyId] = [
            'type' => $event->type,
            'rules' => $event->rules,
            'effective_date' => $event->effectiveDate,
            'end_date' => $event->endDate
        ];
    }

    protected function applyDiscountRuleAdded(DiscountRuleAdded $event): void
    {
        $this->discountRules[$event->ruleId] = [
            'conditions' => $event->conditions,
            'discount_pct' => $event->discountPercentage,
            'priority' => $event->priority,
            'effective_from' => $event->effectiveFrom,
            'effective_until' => $event->effectiveUntil
        ];
    }

    protected function applyDiscountRuleRemoved(DiscountRuleRemoved $event): void
    {
        unset($this->discountRules[$event->ruleId]);
    }

    protected function applyTournéeFrequencyRuleChanged(TournéeFrequencyRuleChanged $event): void
    {
        $this->tournéeFrequencyRules[$event->zoneId] = [
            'min_days_between' => $event->minDaysBetween,
            'max_per_week' => $event->maxPerWeek,
            'preferred_days' => $event->preferredDays
        ];
    }

    protected function applyTournéePlannedForZone(TournéePlannedForZone $event): void
    {
        $this->activeTournées[$event->tournéeId] = [
            'zone_id' => $event->zoneId,
            'date' => $event->date,
            'status' => 'planned',
            'route_id' => $event->routeId,
            'stock_locked' => false,
            'credit_locked' => false
        ];

        // Update zone load
        if (isset($this->zoneQuotas[$event->zoneId])) {
            $this->zoneQuotas[$event->zoneId]['current_load'] += $event->targetVolume;
            $capacity = $this->zoneQuotas[$event->zoneId]['daily_capacity'];
            $this->zoneQuotas[$event->zoneId]['utilization_pct'] = 
                $capacity > 0 ? ($this->zoneQuotas[$event->zoneId]['current_load'] / $capacity) * 100 : 0;
        }
    }

    protected function applyTournéeCancelledForZone(TournéeCancelledForZone $event): void
    {
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['status'] = 'cancelled';
        }
    }

    protected function applyZoneRouteAccepted(ZoneRouteAccepted $event): void
    {
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['status'] = 'accepted';
        }
    }

    protected function applyZoneStockLocked(ZoneStockLocked $event): void
    {
        $this->lockedStock[$event->tournéeId] = $event->stockRequirements;
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['stock_locked'] = true;
        }
    }

    protected function applyZoneCreditLocked(ZoneCreditLocked $event): void
    {
        $this->lockedCredit[$event->tournéeId] = $event->creditRequirements;
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['credit_locked'] = true;
        }
    }

    protected function applyStockReleased(StockReleased $event): void
    {
        unset($this->lockedStock[$event->tournéeId]);
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['stock_locked'] = false;
        }
    }

    protected function applyCreditReleased(CreditReleased $event): void
    {
        unset($this->lockedCredit[$event->tournéeId]);
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['credit_locked'] = false;
        }
    }

    protected function applyZoneRouteNeedsAdaptation(ZoneRouteNeedsAdaptation $event): void
    {
        $this->pendingAdaptations[$event->adaptationId] = [
            'tournée_id' => $event->tournéeId,
            'zone_id' => $event->zoneId,
            'reason' => $event->reason,
            'requested_at' => $event->requestedAt
        ];
        
        if (isset($this->activeTournées[$event->tournéeId])) {
            $this->activeTournées[$event->tournéeId]['status'] = 'needs_adaptation';
        }
    }

    protected function applyZoneCoverageCompromised(ZoneCoverageCompromised $event): void
    {
        // No state change - this is an alert event for monitoring/projectors
    }
}
