<?php

namespace App\Events;

/**
 * Événement émis lorsqu'un point de livraison est visité
 * Alimente les métriques de visite client dans le dashboard
 */
class StopVisited
{
    public string $uuid;
    public int $missionId;
    public int $entrepriseId;
    public int $missionPointId;
    public int $routeId;
    public string $visitedAt;
    public array $deliveryData;

    /**
     * @param array $deliveryData Details: actual_dropped, returns, damages, notes
     */
    public function __construct(
        string $uuid,
        int $missionId,
        int $entrepriseId,
        int $missionPointId,
        int $routeId,
        string $visitedAt,
        array $deliveryData
    ) {
        $this->uuid = $uuid;
        $this->missionId = $missionId;
        $this->entrepriseId = $entrepriseId;
        $this->missionPointId = $missionPointId;
        $this->routeId = $routeId;
        $this->visitedAt = $visitedAt;
        $this->deliveryData = $deliveryData;
    }
}
