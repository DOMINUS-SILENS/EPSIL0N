<?php

namespace App\Traits;

use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\Context;

trait TracesService
{
    protected function trace(string $operation, callable $callback)
    {
        $span = app(TracerInterface::class)
            ->spanBuilder($operation)
            ->setParent(Context::getCurrent())
            ->startSpan();

        try {
            $result = $callback();
            $span->setStatus(0); // OK

            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(1, $e->getMessage()); // Error
            throw $e;
        } finally {
            $span->end();
        }
    }
}
