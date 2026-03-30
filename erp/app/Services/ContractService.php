<?php

namespace App\Services;

use App\Helpers\Logging;
use App\Models\Contract;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContractService
{
    /**
     * Verify all active contracts for a given context.
     *
     * @throws RuntimeException if any contract fails
     */
    public function verify(array $context): void
    {
        $contractClasses = Cache::rememberForever('active_contract_predicates', function () {
            return Contract::where('is_active', true)->pluck('predicate_class')->toArray();
        });

        foreach ($contractClasses as $predicateClass) {
            $predicate = App::make($predicateClass);
            $result = $predicate->evaluate($context);

            // Log decision
            DB::table('decision_audit')->insert([
                'decision_type' => 'contract',
                'context' => json_encode(['contract' => $predicateClass, 'context' => $context]),
                'result' => $result,
                'correlation_id' => Logging::getCorrelationId(),
                'made_at' => now(),
            ]);

            if (! $result) {
                throw new RuntimeException("Contract failed: {$predicateClass}");
            }
        }
    }
}
