<?php

namespace App\Contracts;

interface IntentVerifier
{
    /**
     * Verify that the intent is semantically valid.
     */
    public function verify(array $command): bool;
}
