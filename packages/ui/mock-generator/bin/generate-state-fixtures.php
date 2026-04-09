#!/usr/bin/env php
<?php

declare(strict_types=1);

$stateFile = __DIR__ . '/../../../contracts/state/generated/state.json';
$outputDir = $argv[1] ?? (__DIR__ . '/../fixtures');

if (!is_dir(dirname($outputDir))) {
    mkdir(dirname($outputDir), 0777, true);
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

$stateSpec = json_decode(file_get_contents($stateFile), true, 512, JSON_THROW_ON_ERROR);

$fixture = [
    'generatedAt' => gmdate(DATE_ATOM),
    'canonicalViewStates' => array_map(
        static fn(string $state): array => [
            'state' => $state,
            'sampleObject' => [
                'id' => 'fixture-' . $state,
                'title' => 'Fixture ' . $state,
            ],
        ],
        $stateSpec['canonicalViewStates']
    ),
    'latencyTruthStates' => array_map(
        static fn(string $state): array => [
            'latencyTruth' => $state,
            'transportCode' => match ($state) {
                'durably_queued' => 202,
                'server_committed' => 200,
                'server_rejected' => 422,
                'server_conflicted' => 409,
                default => 102,
            },
        ],
        $stateSpec['latencyTruthStates']
    ),
    'exceptionSeverities' => array_map(
        static fn(string $severity): array => [
            'severity' => $severity,
            'message' => 'Fixture for ' . $severity,
        ],
        $stateSpec['exceptionSeverities']
    ),
];

$target = rtrim($outputDir, '/') . '/state-fixtures.json';
file_put_contents($target, json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);

fwrite(STDOUT, "Generated {$target}\n");
