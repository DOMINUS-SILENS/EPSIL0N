<?php

namespace App\Helpers;

use App\Services\MetricsService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Logging
{
    protected static ?string $correlationId = null;

    public static function setCorrelationId(string $id): void
    {
        static::$correlationId = $id;
    }

    public static function getCorrelationId(): string
    {
        return static::$correlationId ?? (static::$correlationId = (string) Str::uuid());
    }

    public static function info(string $message, array $context = []): void
    {
        static::log('info', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        static::log('error', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        static::log('warning', $message, $context);
    }

    public static function log(string $level, string $message, array $context = []): void
    {

        $metrics = app(MetricsService::class);

        if (! $metrics->recordAttention()) {
            return; // drop log if budget exceeded
        }
        $context['correlation_id'] = static::getCorrelationId();
        Log::log($level, $message, $context);

    }
}
