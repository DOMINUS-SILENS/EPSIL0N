<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CostTracker
{
    public function handle($request, Closure $next)
    {
        DB::enableQueryLog();
        $start = microtime(true);
        $response = $next($request);
        $duration = microtime(true) - $start;
        $queries = DB::getQueryLog();

        $threshold = (float) env('SLOW_QUERY_THRESHOLD', 1.0);
        if ($duration > $threshold) {
            Log::warning('Slow request', [
                'uri' => $request->path(),
                'duration' => $duration,
                'queries' => count($queries),
                'query_log' => $queries, // optional, can be huge
            ]);
        }

        // Optional: abort if too slow
        if ($duration > (float) env('MAX_REQUEST_TIME', 10.0)) {
            abort(503, 'Request timeout');
        }

        DB::disableQueryLog();

        return $response;
    }
}
