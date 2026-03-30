<?php

namespace App\Traits;

use App\Services\ContractService;
use Illuminate\Support\Facades\DB;

trait HasPreCommitGuards
{
    protected function guard(array $context, callable $callback)
    {
        return DB::transaction(function () use ($context, $callback) {
            // Pre‑commit invariant checks
            app(ContractService::class)->verify($context);

            // Execute the operation
            $result = $callback();

            // Additional checks after operation? Could be added.
            return $result;
        });
    }
}
