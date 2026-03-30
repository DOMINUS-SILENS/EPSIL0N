<?php

namespace App\Contracts;

interface Predicate
{
    /**
     * Evaluate the predicate.
     */
    public function evaluate(array $context): bool;
}
