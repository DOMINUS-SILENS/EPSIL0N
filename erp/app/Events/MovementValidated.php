<?php

namespace App\Events;

/**
 * Événement émis lors de la validation d'un mouvement (prévente)
 * Contient toutes les données nécessaires pour les projections analytiques
 */
class MovementValidated
{
    public string $uuid;
    public int $movementId;
    public int $entrepriseId;
    public int $routeId;
    public string $date;
    public float $totalHt;
    public float $totalTtc;
    public int $contactId;
    public array $lines;

    /**
     * @param array $lines Chaque ligne doit contenir: article_id, quantity, price_ht, price_ttc
     */
    public function __construct(
        string $uuid,
        int $movementId,
        int $entrepriseId,
        int $routeId,
        string $date,
        float $totalHt,
        float $totalTtc,
        int $contactId,
        array $lines
    ) {
        $this->uuid = $uuid;
        $this->movementId = $movementId;
        $this->entrepriseId = $entrepriseId;
        $this->routeId = $routeId;
        $this->date = $date;
        $this->totalHt = $totalHt;
        $this->totalTtc = $totalTtc;
        $this->contactId = $contactId;
        $this->lines = $lines;
    }
}
