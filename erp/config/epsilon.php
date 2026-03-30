<?php

return [
    /**
     * Canonical Schema Routing (v11 Refactor)
     * These flags control the transition from legacy to canonical tables.
     */
    'canonical_reads' => env('CANONICAL_READS', false),
    'canonical_writes' => env('CANONICAL_WRITES', false),
    'canonical_dual_write' => env('CANONICAL_DUAL_WRITE', false),
];
