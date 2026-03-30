<?php

namespace App\Services;

use RuntimeException;

class EventSerializer
{
    /**
     * Serializes an event payload into a formally canonical JSON string.
     * Prevents binary representation drift identical logical payloads.
     */
    public static function serialize(array $payload): string
    {
        self::validateNoFloats($payload);
        
        $canonical = self::recursiveKsort($payload);
        
        return json_encode(
            $canonical, 
            JSON_UNESCAPED_UNICODE | 
            JSON_UNESCAPED_SLASHES | 
            JSON_PRESERVE_ZERO_FRACTION | 
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * Recursively sort array keys to guarantee structural stability regardless of PHP array construction order.
     */
    protected static function recursiveKsort(array $array): array
    {
        ksort($array);
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::recursiveKsort($value);
            }
        }
        return $array;
    }

    /**
     * Mathematically forbids IEEE 754 floating-point numbers which exhibit unpredictable cross-platform drift.
     */
    protected static function validateNoFloats($value, string $path = 'root'): void
    {
        if (is_float($value)) {
            throw new RuntimeException("Axiomatic Closure Violation: Float detected at `{$path}`. Financial precision requires BIGINT (Cents).");
        }
        
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                self::validateNoFloats($v, "{$path}.{$k}");
            }
        }
    }
}
