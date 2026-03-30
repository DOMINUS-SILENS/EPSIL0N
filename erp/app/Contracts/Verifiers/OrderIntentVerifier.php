<?php

namespace App\Contracts\Verifiers;

use App\Contracts\IntentVerifier;

class OrderIntentVerifier implements IntentVerifier
{
    public function verify(array $command): bool
    {
        // Example: check that order contains at least one item
        return isset($command['items']) && count($command['items']) > 0;
    }
}
