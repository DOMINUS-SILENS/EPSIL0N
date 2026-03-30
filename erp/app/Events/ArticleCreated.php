<?php

namespace App\Events;

class ArticleCreated
{
    public string $uuid;
    public int $articleId;
    public int $entrepriseId;
    public array $data;
    public array $units;
    public array $taxes;

    public function __construct(
        string $uuid,
        int $articleId,
        int $entrepriseId,
        array $data,
        array $units,
        array $taxes
    ) {
        $this->uuid = $uuid;
        $this->articleId = $articleId;
        $this->entrepriseId = $entrepriseId;
        $this->data = $data;
        $this->units = $units;
        $this->taxes = $taxes;
    }
}
