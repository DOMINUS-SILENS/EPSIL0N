<?php

declare(strict_types=1);

namespace App\Sagas;

use App\Events\Zone\TerrainDisruptionDetected;
use App\Events\Zone\RouteReplanned;
use App\Events\Zone\CriticalCustomerAtRisk;
use App\Events\Zone\AlternativePathEvaluated;
use App\Events\Zone\TerrainAdaptationFailed;
use App\Aggregates\ZoneAggregate;
use Illuminate\Support\Facades\Log;

/**
 * Zone Terrain Adaptation Saga
 * 
 * Orchestrates response to terrain disruptions during route execution.
 * Listens to terrain events and coordinates route replanning.
 * 
 * Responsibilities:
 * - Detect terrain disruptions (road blocked, weather, vehicle issues)
 * - Evaluate alternative paths for affected routes
 * - Trigger route replanning if alternative is feasible
 * - Alert on critical customers at risk when no alternative exists
 * - Emit events for Region KPI updates
 */
class ZoneTerrainAdaptationSaga
{
    private string $sagaId;
    private string $zoneId;
    private string $disruptionId;
    private string $status; // 'detected', 'evaluating', 'replanned', 'failed', 'compensated'
    private array $affectedRoutes;
    private array $alternativePaths;
    private array $failedEvaluations;
    private ?string $startedAt = null;
    private ?string $completedAt = null;

    public function __construct(string $sagaId, string $zoneId, string $disruptionId)
    {
        $this->sagaId = $sagaId;
        $this->zoneId = $zoneId;
        $this->disruptionId = $disruptionId;
        $this->status = 'initialized';
        $this->affectedRoutes = [];
        $this->alternativePaths = [];
        $this->failedEvaluations = [];
    }

    /**
     * Start saga when terrain disruption is detected
     */
    public function onTerrainDisruptionDetected(TerrainDisruptionDetected $event): void
    {
        $this->startedAt = now()->toDateTimeString();
        $this->status = 'detected';
        $this->affectedRoutes = $event->affectedRoutes;

        Log::info('Terrain adaptation saga started', [
            'saga_id' => $this->sagaId,
            'zone_id' => $this->zoneId,
            'disruption_id' => $this->disruptionId,
            'type' => $event->type,
            'affected_routes_count' => count($this->affectedRoutes)
        ]);

        // Begin evaluation phase
        $this->evaluateAlternatives($event);
    }

    /**
     * Evaluate alternative paths for affected routes
     */
    private function evaluateAlternatives(TerrainDisruptionDetected $event): void
    {
        $this->status = 'evaluating';
        $routingService = app(\App\Services\RoutingService::class);

        foreach ($this->affectedRoutes as $routeId) {
            try {
                $alternative = $routingService->findAlternativePath(
                    zoneId: $this->zoneId,
                    routeId: $routeId,
                    disruptionType: $event->type,
                    disruptionLocation: $event->location,
                    constraints: $event->constraints
                );

                if ($alternative->isFeasible()) {
                    $this->alternativePaths[$routeId] = [
                        'path' => $alternative->getPath(),
                        'additional_time' => $alternative->getAdditionalTime(),
                        'additional_distance' => $alternative->getAdditionalDistance(),
                        'affected_customers' => $alternative->getAffectedCustomers(),
                        'feasible' => true
                    ];

                    event(new AlternativePathEvaluated(
                        zoneId: $this->zoneId,
                        sagaId: $this->sagaId,
                        routeId: $routeId,
                        alternative: $this->alternativePaths[$routeId],
                        evaluatedAt: now()->toDateTimeString()
                    ));
                } else {
                    $this->failedEvaluations[$routeId] = [
                        'reason' => $alternative->getFailureReason(),
                        'affected_customers' => $alternative->getAffectedCustomers()
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Alternative path evaluation failed', [
                    'saga_id' => $this->sagaId,
                    'route_id' => $routeId,
                    'error' => $e->getMessage()
                ]);

                $this->failedEvaluations[$routeId] = [
                    'reason' => 'EVALUATION_ERROR',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Decide next action based on evaluation results
        $this->makeAdaptationDecision();
    }

    /**
     * Make adaptation decision based on evaluation results
     */
    private function makeAdaptationDecision(): void
    {
        $hasAlternatives = !empty($this->alternativePaths);
        $hasFailures = !empty($this->failedEvaluations);

        if ($hasAlternatives) {
            // Replan routes with alternatives
            foreach ($this->alternativePaths as $routeId => $alternative) {
                $this->replanRoute($routeId, $alternative);
            }
        }

        if ($hasFailures) {
            // Handle routes with no alternatives
            foreach ($this->failedEvaluations as $routeId => $failure) {
                $this->handleUnrecoverableRoute($routeId, $failure);
            }
        }

        // Complete saga
        $this->completedAt = now()->toDateTimeString();
        $this->status = ($hasAlternatives && !$hasFailures) 
            ? 'replanned' 
            : (($hasAlternatives && $hasFailures) ? 'partially_replanned' : 'failed');

        Log::info('Terrain adaptation saga completed', [
            'saga_id' => $this->sagaId,
            'status' => $this->status,
            'replanned_routes' => count($this->alternativePaths),
            'failed_routes' => count($this->failedEvaluations)
        ]);
    }

    /**
     * Replan a route with alternative path
     */
    private function replanRoute(string $routeId, array $alternative): void
    {
        try {
            $zone = ZoneAggregate::retrieve($this->zoneId);
            
            $zone->handleTerrainDisruption(
                disruptionId: $this->disruptionId,
                type: 'road_blocked', // Would be passed from original event
                impact: [
                    'affected_routes' => [$routeId],
                    'affected_customers' => $alternative['affected_customers'],
                    'severity' => $alternative['additional_time'] > 30 ? 'high' : 'medium'
                ],
                alternativePath: $alternative['path']
            );

            $zone->persist();

            event(new RouteReplanned(
                zoneId: $this->zoneId,
                routeId: $routeId,
                disruptionId: $this->disruptionId,
                type: 'road_blocked',
                alternativePath: $alternative['path'],
                affectedCustomers: $alternative['affected_customers'],
                replannedAt: now()->toDateTimeString()
            ));

        } catch (\Exception $e) {
            Log::error('Route replanning failed', [
                'saga_id' => $this->sagaId,
                'route_id' => $routeId,
                'error' => $e->getMessage()
            ]);

            // Move to failed evaluations
            $this->failedEvaluations[$routeId] = [
                'reason' => 'REPLAN_FAILED',
                'error' => $e->getMessage()
            ];
            unset($this->alternativePaths[$routeId]);
        }
    }

    /**
     * Handle unrecoverable route failure
     */
    private function handleUnrecoverableRoute(string $routeId, array $failure): void
    {
        // Identify critical customers at risk
        $criticalCustomers = $this->identifyCriticalCustomers(
            $routeId, 
            $failure['affected_customers'] ?? []
        );

        foreach ($criticalCustomers as $customer) {
            event(new CriticalCustomerAtRisk(
                zoneId: $this->zoneId,
                customerId: $customer['id'],
                reason: "Route {$routeId} unrecoverable: {$failure['reason']}",
                detectedAt: now()->toDateTimeString(),
                priority: $customer['priority']
            ));
        }

        // Emit saga failure event for monitoring
        event(new TerrainAdaptationFailed(
            sagaId: $this->sagaId,
            zoneId: $this->zoneId,
            routeId: $routeId,
            reason: $failure['reason'],
            criticalCustomers: array_column($criticalCustomers, 'id'),
            failedAt: now()->toDateTimeString()
        ));
    }

    /**
     * Identify critical customers from affected list
     */
    private function identifyCriticalCustomers(string $routeId, array $affectedCustomers): array
    {
        $zone = ZoneAggregate::retrieve($this->zoneId);
        $critical = [];

        foreach ($affectedCustomers as $customerId) {
            // Check if mandatory customer
            if ($zone->isMandatoryCustomer($customerId)) {
                $priority = $zone->getCustomerPriority($customerId);
                $lastVisit = $zone->getCustomerLastVisit($customerId);
                
                // Calculate risk score
                $riskScore = $this->calculateRiskScore($priority, $lastVisit);
                
                if ($riskScore >= 7) { // High priority or overdue
                    $critical[] = [
                        'id' => $customerId,
                        'priority' => $priority,
                        'risk_score' => $riskScore
                    ];
                }
            }
        }

        return $critical;
    }

    /**
     * Calculate customer risk score
     */
    private function calculateRiskScore(int $priority, ?string $lastVisit): int
    {
        $score = (6 - $priority) * 2; // Priority 1 = 10 points, Priority 5 = 2 points
        
        if ($lastVisit) {
            $daysSinceVisit = now()->diffInDays($lastVisit);
            if ($daysSinceVisit > 7) {
                $score += min($daysSinceVisit - 7, 5); // Up to 5 additional points
            }
        } else {
            $score += 5; // Never visited
        }

        return min($score, 15);
    }

    /**
     * Get saga state for persistence
     */
    public function getState(): array
    {
        return [
            'saga_id' => $this->sagaId,
            'zone_id' => $this->zoneId,
            'disruption_id' => $this->disruptionId,
            'status' => $this->status,
            'affected_routes' => $this->affectedRoutes,
            'alternative_paths' => $this->alternativePaths,
            'failed_evaluations' => $this->failedEvaluations,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt
        ];
    }

    /**
     * Restore saga from persisted state
     */
    public static function restore(array $state): self
    {
        $saga = new self($state['saga_id'], $state['zone_id'], $state['disruption_id']);
        $saga->status = $state['status'];
        $saga->affectedRoutes = $state['affected_routes'];
        $saga->alternativePaths = $state['alternative_paths'];
        $saga->failedEvaluations = $state['failed_evaluations'];
        $saga->startedAt = $state['started_at'];
        $saga->completedAt = $state['completed_at'];
        return $saga;
    }
}
