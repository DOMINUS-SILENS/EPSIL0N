<?php

declare(strict_types=1);

namespace App\Aggregates;

use App\Events\Zone\ZoneRegistered;
use App\Events\Zone\ZoneCapacityUpdated;
use App\Events\Zone\ZoneRoutePlanned;
use App\Events\Zone\ZoneRouteAccepted;
use App\Events\Zone\ZoneRouteRejected;
use App\Events\Zone\ZoneRouteAdapted;
use App\Events\Zone\ZoneRouteStarted;
use App\Events\Zone\ZoneRouteCompleted;
use App\Events\Zone\ZoneRouteAborted;
use App\Events\Zone\ZoneCapacityBreached;
use App\Events\Zone\CriticalCustomerAtRisk;
use App\Events\Zone\FallbackPricingApplied;
use App\Events\Zone\ZoneTerrainConstraintAdded;
use App\Events\Zone\ZoneTerrainConstraintRemoved;
use App\Events\Zone\StopCompleted;
use App\Events\Zone\StopSkipped;
use App\Events\Zone\RouteReplanned;

/**
 * Zone Aggregate - Execution + Adaptation
 * 
 * Invariants:
 * - Per-day capacity limits enforced
 * - Mandatory stops (key customers) must be covered
 * - Terrain constraints (vehicle, time windows) validated
 * - Adherence to Region policies
 * - Route replanning within adaptation SLA
 */
class ZoneAggregate extends AggregateRoot
{
    private string $zoneId;
    private ?string $regionId = null;
    private ?string $name = null;
    
    // Capacity management
    private int $dailyCapacity = 0;
    private int $currentLoad = 0;
    private float $utilizationThreshold = 0.9; // 90% warning
    
    // Route management
    private array $activeRoutes = []; // route_id => [status, customers, vehicles, start_time]
    private array $pendingRoutes = []; // routes from Region awaiting decision
    private array $adaptedRoutes = []; // routes that were adapted
    private array $completedRoutes = []; // historical
    
    // Customer management
    private array $mandatoryCustomers = []; // customer_id => [priority, min_frequency]
    private array $customerVisitHistory = []; // customer_id => [last_visit_date, visit_count]
    
    // Terrain constraints
    private array $terrainConstraints = []; // constraint_id => [type, parameters, active_hours]
    private array $vehicleConstraints = []; // vehicle_type => [max_capacity, restrictions]
    private array $timeWindows = []; // customer_id => [start, end, flex_minutes]
    
    // Adaptation tracking
    private int $adaptationSlaMinutes = 30; // Max time to adapt a route
    private array $pendingAdaptations = []; // adaptation_id => [requested_at, deadline, status]
    
    // Critical customer alerts
    private array $atRiskCustomers = []; // customer_id => [reason, detected_at]

    protected function __construct(string $uuid)
    {
        parent::__construct($uuid);
        $this->zoneId = $uuid;
    }

    /**
     * Register zone with Region
     */
    public function register(
        string $regionId,
        string $name,
        int $dailyCapacity,
        array $terrainConstraints = [],
        array $mandatoryCustomers = []
    ): void {
        // Invariant: Capacity must be positive
        if ($dailyCapacity <= 0) {
            throw new \InvalidArgumentException('Daily capacity must be positive');
        }

        // Invariant: Mandatory customers must have valid priorities
        foreach ($mandatoryCustomers as $customerId => $config) {
            if (!isset($config['priority']) || $config['priority'] < 1 || $config['priority'] > 5) {
                throw new \InvalidArgumentException("Customer {$customerId} must have priority 1-5");
            }
        }

        $this->recordThat(new ZoneRegistered(
            $this->zoneId,
            $regionId,
            $name,
            $dailyCapacity,
            $terrainConstraints,
            $mandatoryCustomers
        ));
    }

    /**
     * Update zone capacity
     */
    public function updateCapacity(
        int $newCapacity,
        string $reason,
        ?float $newUtilizationThreshold = null
    ): void {
        if ($newCapacity <= 0) {
            throw new \InvalidArgumentException('Daily capacity must be positive');
        }

        // Invariant: Cannot reduce capacity below current load
        if ($newCapacity < $this->currentLoad) {
            throw new \DomainException(
                "Cannot reduce capacity to {$newCapacity} with current load {$this->currentLoad}"
            );
        }

        $this->recordThat(new ZoneCapacityUpdated(
            $this->zoneId,
            $newCapacity,
            $reason,
            $newUtilizationThreshold
        ));
    }

    /**
     * Plan zone route from Region tournée
     * Zone validates against local constraints
     */
    public function planRoute(
        string $routeId,
        string $tournéeId,
        string $date,
        string $pricingProfileId,
        float $targetVolume,
        array $customers,
        array $vehicles,
        array $terrainConditions = []
    ): void {
        // Invariant: Check capacity
        if ($this->currentLoad + $targetVolume > $this->dailyCapacity) {
            throw new \DomainException(
                "Zone {$this->zoneId} capacity exceeded: load would be " . 
                ($this->currentLoad + $targetVolume) . " > capacity {$this->dailyCapacity}"
            );
        }

        // Invariant: Check terrain constraints
        foreach ($this->terrainConstraints as $constraint) {
            if (!$this->isConstraintSatisfied($constraint, $terrainConditions, $vehicles)) {
                throw new \DomainException(
                    "Terrain constraint not satisfied: {$constraint['type']}"
                );
            }
        }

        // Invariant: Mandatory customers must be included
        $missingMandatory = array_diff_key(
            $this->mandatoryCustomers,
            array_flip(array_column($customers, 'customer_id'))
        );
        if (!empty($missingMandatory)) {
            throw new \DomainException(
                'Mandatory customers missing: ' . implode(', ', array_keys($missingMandatory))
            );
        }

        // Invariant: Time windows must be respected
        foreach ($customers as $customer) {
            if (isset($this->timeWindows[$customer['customer_id']])) {
                if (!$this->isWithinTimeWindow($customer, $this->timeWindows[$customer['customer_id']])) {
                    throw new \DomainException(
                        "Customer {$customer['customer_id']} outside time window"
                    );
                }
            }
        }

        $this->recordThat(new ZoneRoutePlanned(
            $this->zoneId,
            $routeId,
            $tournéeId,
            $date,
            $pricingProfileId,
            $targetVolume,
            $customers,
            $vehicles
        ));
    }

    /**
     * Accept planned route (Zone → Region acceptance)
     */
    public function acceptRoute(
        string $routeId,
        array $stockRequirements,
        array $creditRequirements,
        ?string $acceptedAt = null
    ): void {
        if (!isset($this->pendingRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not found in pending routes");
        }

        $route = $this->pendingRoutes[$routeId];

        $this->recordThat(new ZoneRouteAccepted(
            $this->zoneId,
            $routeId,
            $route['tournée_id'],
            $acceptedAt ?? now()->toDateTimeString(),
            $stockRequirements,
            $creditRequirements
        ));
    }

    /**
     * Reject route with adaptation request
     */
    public function rejectRoute(
        string $routeId,
        string $reason,
        array $requestedChanges,
        ?string $adaptationDeadline = null
    ): void {
        if (!isset($this->pendingRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not found");
        }

        $adaptationId = $this->generateAdaptationId();
        $deadline = $adaptationDeadline ?? now()->addMinutes($this->adaptationSlaMinutes)->toDateTimeString();

        $this->recordThat(new ZoneRouteRejected(
            $this->zoneId,
            $routeId,
            $this->pendingRoutes[$routeId]['tournée_id'],
            $reason,
            $requestedChanges,
            $adaptationId,
            $deadline
        ));
    }

    /**
     * Adapt route based on new conditions or Region feedback
     */
    public function adaptRoute(
        string $routeId,
        array $changes, // [volume_adjustment, excluded_customers, added_customers, vehicle_change]
        string $reason,
        ?string $adaptedBy = null
    ): void {
        if (!isset($this->activeRoutes[$routeId]) && !isset($this->pendingRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not found");
        }

        $originalVolume = $this->pendingRoutes[$routeId]['target_volume'] ?? 0;
        $newVolume = $originalVolume + ($changes['volume_adjustment'] ?? 0);

        // Invariant: Adapted route must still fit capacity
        if ($this->currentLoad - $originalVolume + $newVolume > $this->dailyCapacity) {
            throw new \DomainException("Adapted route exceeds zone capacity");
        }

        // Invariant: Cannot exclude mandatory customers unless explicitly authorized
        if (isset($changes['excluded_customers'])) {
            $excludedMandatory = array_intersect(
                $changes['excluded_customers'],
                array_keys($this->mandatoryCustomers)
            );
            if (!empty($excludedMandatory) && !($changes['mandatory_override'] ?? false)) {
                throw new \DomainException(
                    'Cannot exclude mandatory customers without override: ' . 
                    implode(', ', $excludedMandatory)
                );
            }
        }

        $this->recordThat(new ZoneRouteAdapted(
            $this->zoneId,
            $routeId,
            $this->pendingRoutes[$routeId]['tournée_id'] ?? $this->activeRoutes[$routeId]['tournée_id'],
            $changes,
            $reason,
            $adaptedBy ?? 'system',
            now()->toDateTimeString()
        ));
    }

    /**
     * Start route execution
     */
    public function startRoute(string $routeId, array $vehicles, string $startedAt): void
    {
        if (!isset($this->activeRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not active");
        }

        if ($this->activeRoutes[$routeId]['status'] !== 'planned') {
            throw new \DomainException("Route {$routeId} must be in planned state to start");
        }

        $this->recordThat(new ZoneRouteStarted(
            $this->zoneId,
            $routeId,
            $vehicles,
            $startedAt
        ));
    }

    /**
     * Complete route execution
     */
    public function completeRoute(
        string $routeId,
        array $completedStops,
        array $skippedStops,
        array $metrics,
        string $completedAt
    ): void {
        if (!isset($this->activeRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not active");
        }

        $this->recordThat(new ZoneRouteCompleted(
            $this->zoneId,
            $routeId,
            $completedStops,
            $skippedStops,
            $metrics,
            $completedAt
        ));

        // Update customer visit history
        foreach ($completedStops as $stop) {
            $this->customerVisitHistory[$stop['customer_id']] = [
                'last_visit_date' => $completedAt,
                'visit_count' => ($this->customerVisitHistory[$stop['customer_id']]['visit_count'] ?? 0) + 1
            ];
        }

        // Clear at-risk status for completed customers
        foreach ($completedStops as $stop) {
            unset($this->atRiskCustomers[$stop['customer_id']]);
        }
    }

    /**
     * Abort route due to emergency/force majeure
     */
    public function abortRoute(string $routeId, string $reason, string $abortedAt): void
    {
        if (!isset($this->activeRoutes[$routeId])) {
            throw new \DomainException("Route {$routeId} not active");
        }

        $this->recordThat(new ZoneRouteAborted(
            $this->zoneId,
            $routeId,
            $reason,
            $abortedAt
        ));

        // Mark mandatory customers as at-risk
        $routeCustomers = $this->activeRoutes[$routeId]['customers'] ?? [];
        foreach ($routeCustomers as $customer) {
            if (isset($this->mandatoryCustomers[$customer['customer_id']])) {
                $this->atRiskCustomers[$customer['customer_id']] = [
                    'reason' => "Route aborted: {$reason}",
                    'detected_at' => $abortedAt,
                    'route_id' => $routeId
                ];

                $this->recordThat(new CriticalCustomerAtRisk(
                    $this->zoneId,
                    $customer['customer_id'],
                    "Route aborted: {$reason}",
                    $abortedAt,
                    $this->mandatoryCustomers[$customer['customer_id']]['priority']
                ));
            }
        }
    }

    /**
     * Handle terrain disruption - trigger route replanning
     */
    public function handleTerrainDisruption(
        string $disruptionId,
        string $type, // 'road_blocked', 'weather_alert', 'vehicle_unavailable'
        array $impact, // [affected_routes, affected_customers, severity]
        ?array $alternativePath = null
    ): void {
        foreach ($impact['affected_routes'] as $routeId) {
            if (isset($this->activeRoutes[$routeId])) {
                // Check if alternative is feasible
                if ($alternativePath) {
                    $this->recordThat(new RouteReplanned(
                        $this->zoneId,
                        $routeId,
                        $disruptionId,
                        $type,
                        $alternativePath,
                        $impact['affected_customers'],
                        now()->toDateTimeString()
                    ));
                } else {
                    // No alternative - mark critical customers at risk
                    foreach ($impact['affected_customers'] as $customerId) {
                        if (isset($this->mandatoryCustomers[$customerId])) {
                            $this->recordThat(new CriticalCustomerAtRisk(
                                $this->zoneId,
                                $customerId,
                                "Terrain disruption: {$type}",
                                now()->toDateTimeString(),
                                $this->mandatoryCustomers[$customerId]['priority']
                            ));
                        }
                    }
                }
            }
        }
    }

    /**
     * Record stop completion during route execution
     */
    public function recordStopCompleted(
        string $routeId,
        string $customerId,
        string $arrivedAt,
        ?string $departedAt = null,
        array $transactions = [] // [orders, payments, visits]
    ): void {
        $this->recordThat(new StopCompleted(
            $this->zoneId,
            $routeId,
            $customerId,
            $arrivedAt,
            $departedAt,
            $transactions
        ));
    }

    /**
     * Record skipped stop (customer unavailable, closed, etc.)
     */
    public function recordStopSkipped(
        string $routeId,
        string $customerId,
        string $reason,
        string $attemptedAt,
        ?string $rescheduleDate = null
    ): void {
        $this->recordThat(new StopSkipped(
            $this->zoneId,
            $routeId,
            $customerId,
            $reason,
            $attemptedAt,
            $rescheduleDate
        ));

        // If mandatory customer, trigger at-risk alert
        if (isset($this->mandatoryCustomers[$customerId])) {
            $this->recordThat(new CriticalCustomerAtRisk(
                $this->zoneId,
                $customerId,
                "Stop skipped: {$reason}",
                $attemptedAt,
                $this->mandatoryCustomers[$customerId]['priority']
            ));
        }
    }

    /**
     * Apply fallback pricing when original pricing fails
     */
    public function applyFallbackPricing(
        string $routeId,
        string $customerId,
        string $originalPricingProfileId,
        string $fallbackPricingProfileId,
        string $reason
    ): void {
        $this->recordThat(new FallbackPricingApplied(
            $this->zoneId,
            $routeId,
            $customerId,
            $originalPricingProfileId,
            $fallbackPricingProfileId,
            $reason,
            now()->toDateTimeString()
        ));
    }

    /**
     * Add terrain constraint
     */
    public function addTerrainConstraint(
        string $constraintId,
        string $type, // 'vehicle_restriction', 'time_window', 'access_limitation'
        array $parameters,
        ?array $activeHours = null
    ): void {
        $this->recordThat(new ZoneTerrainConstraintAdded(
            $this->zoneId,
            $constraintId,
            $type,
            $parameters,
            $activeHours
        ));
    }

    /**
     * Remove terrain constraint
     */
    public function removeTerrainConstraint(string $constraintId, string $reason): void
    {
        $this->recordThat(new ZoneTerrainConstraintRemoved(
            $this->zoneId,
            $constraintId,
            $reason
        ));
    }

    /**
     * Check if zone capacity is breached
     */
    public function checkCapacityBreach(): void
    {
        $utilization = $this->getUtilization();
        if ($utilization >= 100) {
            $this->recordThat(new ZoneCapacityBreached(
                $this->zoneId,
                $this->currentLoad,
                $this->dailyCapacity,
                $utilization,
                now()->toDateTimeString()
            ));
        }
    }

    /**
     * Get current utilization percentage
     */
    public function getUtilization(): float
    {
        return $this->dailyCapacity > 0 
            ? ($this->currentLoad / $this->dailyCapacity) * 100 
            : 0;
    }

    /**
     * Generate unique adaptation ID
     */
    private function generateAdaptationId(): string
    {
        return 'adapt_' . $this->zoneId . '_' . now()->format('YmdHis') . '_' . uniqid();
    }

    /**
     * Check if terrain constraint is satisfied
     */
    private function isConstraintSatisfied(array $constraint, array $conditions, array $vehicles): bool
    {
        // Implementation depends on constraint type
        switch ($constraint['type']) {
            case 'vehicle_restriction':
                foreach ($vehicles as $vehicle) {
                    if (in_array($vehicle['type'], $constraint['parameters']['restricted_types'] ?? [])) {
                        return false;
                    }
                }
                return true;
                
            case 'time_window':
                $hour = (int) now()->format('H');
                $allowedHours = $constraint['parameters']['allowed_hours'] ?? range(6, 18);
                return in_array($hour, $allowedHours);
                
            case 'weather':
                return !in_array($conditions['weather'] ?? 'clear', 
                    $constraint['parameters']['blocked_conditions'] ?? []);
                
            default:
                return true;
        }
    }

    /**
     * Check if customer visit is within time window
     */
    private function isWithinTimeWindow(array $customer, array $window): bool
    {
        if (!isset($customer['planned_time'])) {
            return true; // No planned time means no constraint
        }
        
        $planned = strtotime($customer['planned_time']);
        $start = strtotime($window['start']);
        $end = strtotime($window['end']);
        
        return $planned >= $start && $planned <= $end;
    }

    // ========== Event Application Methods ==========

    protected function applyZoneRegistered(ZoneRegistered $event): void
    {
        $this->regionId = $event->regionId;
        $this->name = $event->name;
        $this->dailyCapacity = $event->dailyCapacity;
        $this->terrainConstraints = $event->terrainConstraints;
        $this->mandatoryCustomers = $event->mandatoryCustomers;
    }

    protected function applyZoneCapacityUpdated(ZoneCapacityUpdated $event): void
    {
        $this->dailyCapacity = $event->newCapacity;
        if ($event->newUtilizationThreshold !== null) {
            $this->utilizationThreshold = $event->newUtilizationThreshold;
        }
    }

    protected function applyZoneRoutePlanned(ZoneRoutePlanned $event): void
    {
        $this->pendingRoutes[$event->routeId] = [
            'tournée_id' => $event->tournéeId,
            'date' => $event->date,
            'pricing_profile_id' => $event->pricingProfileId,
            'target_volume' => $event->targetVolume,
            'customers' => $event->customers,
            'vehicles' => $event->vehicles,
            'status' => 'planned',
            'planned_at' => $event->occurredAt ?? now()->toDateTimeString()
        ];
        $this->currentLoad += $event->targetVolume;
    }

    protected function applyZoneRouteAccepted(ZoneRouteAccepted $event): void
    {
        if (isset($this->pendingRoutes[$event->routeId])) {
            $this->activeRoutes[$event->routeId] = array_merge(
                $this->pendingRoutes[$event->routeId],
                [
                    'status' => 'accepted',
                    'accepted_at' => $event->acceptedAt,
                    'stock_locked' => true,
                    'credit_locked' => true
                ]
            );
            unset($this->pendingRoutes[$event->routeId]);
        }
    }

    protected function applyZoneRouteRejected(ZoneRouteRejected $event): void
    {
        if (isset($this->pendingRoutes[$event->routeId])) {
            $this->currentLoad -= $this->pendingRoutes[$event->routeId]['target_volume'];
            $this->adaptedRoutes[$event->routeId] = $this->pendingRoutes[$event->routeId];
            $this->adaptedRoutes[$event->routeId]['status'] = 'rejected';
            $this->adaptedRoutes[$event->routeId]['rejection'] = [
                'reason' => $event->reason,
                'requested_changes' => $event->requestedChanges,
                'adaptation_id' => $event->adaptationId
            ];
            $this->pendingAdaptations[$event->adaptationId] = [
                'route_id' => $event->routeId,
                'requested_at' => $event->occurredAt ?? now()->toDateTimeString(),
                'deadline' => $event->deadline,
                'status' => 'pending'
            ];
            unset($this->pendingRoutes[$event->routeId]);
        }
    }

    protected function applyZoneRouteAdapted(ZoneRouteAdapted $event): void
    {
        $adaptationId = $this->findAdaptationIdForRoute($event->routeId);
        if ($adaptationId) {
            $this->pendingAdaptations[$adaptationId]['status'] = 'adapted';
        }

        // Update route with adapted changes
        if (isset($this->adaptedRoutes[$event->routeId])) {
            $this->adaptedRoutes[$event->routeId]['adaptations'][] = [
                'changes' => $event->changes,
                'reason' => $event->reason,
                'adapted_by' => $event->adaptedBy,
                'adapted_at' => $event->adaptedAt
            ];
        }

        // If route was re-added to pending, move it to active
        if (isset($this->pendingRoutes[$event->routeId])) {
            $this->activeRoutes[$event->routeId] = $this->pendingRoutes[$event->routeId];
            $this->activeRoutes[$event->routeId]['status'] = 'accepted';
            unset($this->pendingRoutes[$event->routeId]);
        }
    }

    protected function applyZoneRouteStarted(ZoneRouteStarted $event): void
    {
        if (isset($this->activeRoutes[$event->routeId])) {
            $this->activeRoutes[$event->routeId]['status'] = 'in_progress';
            $this->activeRoutes[$event->routeId]['started_at'] = $event->startedAt;
            $this->activeRoutes[$event->routeId]['vehicles'] = $event->vehicles;
        }
    }

    protected function applyZoneRouteCompleted(ZoneRouteCompleted $event): void
    {
        if (isset($this->activeRoutes[$event->routeId])) {
            $route = $this->activeRoutes[$event->routeId];
            $route['status'] = 'completed';
            $route['completed_at'] = $event->completedAt;
            $route['metrics'] = $event->metrics;
            
            $this->completedRoutes[$event->routeId] = $route;
            $this->currentLoad -= $route['target_volume'] ?? 0;
            
            unset($this->activeRoutes[$event->routeId]);
        }
    }

    protected function applyZoneRouteAborted(ZoneRouteAborted $event): void
    {
        if (isset($this->activeRoutes[$event->routeId])) {
            $route = $this->activeRoutes[$event->routeId];
            $route['status'] = 'aborted';
            $route['aborted_at'] = $event->abortedAt;
            $route['abort_reason'] = $event->reason;
            
            $this->currentLoad -= $route['target_volume'] ?? 0;
            unset($this->activeRoutes[$event->routeId]);
        }
    }

    protected function applyStopCompleted(StopCompleted $event): void
    {
        // Update visit history
        $this->customerVisitHistory[$event->customerId] = [
            'last_visit_date' => $event->arrivedAt,
            'visit_count' => ($this->customerVisitHistory[$event->customerId]['visit_count'] ?? 0) + 1,
            'route_id' => $event->routeId
        ];
    }

    protected function applyStopSkipped(StopSkipped $event): void
    {
        // No state change - purely for tracking/logging
    }

    protected function applyCriticalCustomerAtRisk(CriticalCustomerAtRisk $event): void
    {
        $this->atRiskCustomers[$event->customerId] = [
            'reason' => $event->reason,
            'detected_at' => $event->detectedAt,
            'priority' => $event->priority
        ];
    }

    protected function applyRouteReplanned(RouteReplanned $event): void
    {
        if (isset($this->activeRoutes[$event->routeId])) {
            $this->activeRoutes[$event->routeId]['replanned'] = [
                'disruption_id' => $event->disruptionId,
                'disruption_type' => $event->type,
                'alternative_path' => $event->alternativePath,
                'replanned_at' => $event->replannedAt
            ];
        }
    }

    protected function applyZoneTerrainConstraintAdded(ZoneTerrainConstraintAdded $event): void
    {
        $this->terrainConstraints[$event->constraintId] = [
            'type' => $event->type,
            'parameters' => $event->parameters,
            'active_hours' => $event->activeHours
        ];
    }

    protected function applyZoneTerrainConstraintRemoved(ZoneTerrainConstraintRemoved $event): void
    {
        unset($this->terrainConstraints[$event->constraintId]);
    }

    protected function applyZoneCapacityBreached(ZoneCapacityBreached $event): void
    {
        // Alert event - no state change
    }

    protected function applyFallbackPricingApplied(FallbackPricingApplied $event): void
    {
        // Tracking event - no state change
    }

    /**
     * Find adaptation ID for a route
     */
    private function findAdaptationIdForRoute(string $routeId): ?string
    {
        foreach ($this->pendingAdaptations as $id => $adaptation) {
            if ($adaptation['route_id'] === $routeId) {
                return $id;
            }
        }
        return null;
    }
}
