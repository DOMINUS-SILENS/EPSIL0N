<?php

namespace App\Events;

class ArticleUnitsUpdated
{
    public string $uuid;
    public int $articleId;
    public int $entrepriseId;
    public array $unitUpdates;

    public function __construct(
        string $uuid,
        int $articleId,
        int $entrepriseId,
        array $unitUpdates
    ) {
        $this->uuid = $uuid;
        $this->articleId = $articleId;
        $this->entrepriseId = $entrepriseId;
        $this->unitUpdates = $unitUpdates;
    }
}
