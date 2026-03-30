<?php

namespace App\Aggregates;

use App\Events\ArticleCreated;
use App\Events\ArticleUnitsUpdated;

class ArticleAggregate extends AggregateRoot
{
    public function create(array $data, array $units, array $taxes): static
    {
        // Execute ContractService invariants here
        $this->recordThat(new ArticleCreated(
            $this->uuid(),
            $data['article_id'],
            $data['entreprise_id'],
            $data,
            $units,
            $taxes
            ));

        return $this;
    }

    public function updateUnits(int $articleId, int $entrepriseId, array $unitUpdates): static
    {
        // Business Logic validation checking unit existence
        $this->recordThat(new ArticleUnitsUpdated(
            $this->uuid(),
            $articleId,
            $entrepriseId,
            $unitUpdates
            ));

        return $this;
    }
}
