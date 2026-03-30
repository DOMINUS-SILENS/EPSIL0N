<?php

namespace App\Http\Middleware;

use App\Helpers\Logging;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\TracerInterface;

class TraceMiddleware
{
    protected TracerInterface $tracer;

    public function __construct(TracerInterface $tracer)
    {
        $this->tracer = $tracer;
    }

    public function handle(Request $request, Closure $next)
    {
        $span = $this->tracer->spanBuilder($request->method().' '.$request->path())
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();

        // Set correlation ID
        $correlationId = $request->header('X-Correlation-ID', (string) Str::uuid());
        $span->setAttribute('http.method', $request->method());
        $span->setAttribute('http.url', $request->fullUrl());
        $span->setAttribute('correlation_id', $correlationId);
        $span->setAttribute('user_id', auth()->id());

        // Store correlation ID in request for later use
        $request->headers->set('X-Correlation-ID', $correlationId);

        $response = $next($request);

        $span->setAttribute('http.status_code', $response->getStatusCode());
        $span->end();

        // Add correlation ID to response headers
        // In TraceMiddleware
        $request->headers->set('X-Correlation-ID', $correlationId);
        Logging::setCorrelationId($correlationId);

        return $response;
    }
}
