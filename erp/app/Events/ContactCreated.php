<?php

namespace App\Events;

class ContactCreated
{
    public string $uuid;
    public int $contactId;
    public int $entrepriseId;
    public ?string $nom;
    public ?string $prenom;
    public ?int $entrepriseId;
    public ?string $raisonSociale;

    public function __construct(
        string $uuid,
        int $contactId,
        int $entrepriseId,
        ?string $nom,
        ?string $prenom,
        ?int $entrepriseId,
        ?string $raisonSociale
    ) {
        $this->uuid = $uuid;
        $this->contactId = $contactId;
        $this->entrepriseId = $entrepriseId;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->entrepriseId = $entrepriseId;
        $this->raisonSociale = $raisonSociale;
    }
}
