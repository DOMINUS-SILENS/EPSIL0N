#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../../..');
if ($root === false) {
    fwrite(STDERR, "Could not resolve repository root.\n");
    exit(1);
}

$frontendRoot = $root . '/apps/frontend/src';
$violations = [];

$allowedTopLevelDirectories = ['actions', 'components', 'contracts', 'routes', 'state'];
$allowedTopLevelFiles = ['semanticContracts.ts'];
$allowedPrimitiveFiles = [
    'ActionCluster.tsx',
    'ApprovalStack.tsx',
    'AuditRail.tsx',
    'ConflictBanner.tsx',
    'DecisionPanel.tsx',
    'ExceptionDrawer.tsx',
    'RecordCard.tsx',
    'StateStrip.tsx',
    'SyncBadge.tsx',
    'WorkflowHeader.tsx',
];
$allowedStateFiles = [
    'aggregateState.ts',
    'conflictState.ts',
    'permissionState.ts',
    'syncState.ts',
];
$allowedActionContexts = [
    'accounts-payable',
    'accounts-receivable',
    'inventory',
    'order-fulfillment',
    'organization',
    'procurement',
    'reporting',
];

if (!is_dir($frontendRoot)) {
    fwrite(STDERR, "E_MISPLACED_BOUNDARY: missing apps/frontend/src\n");
    exit(1);
}

foreach (scandir($frontendRoot) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    $path = $frontendRoot . '/' . $entry;
    if (is_dir($path) && !in_array($entry, $allowedTopLevelDirectories, true)) {
        $violations[] = "E_MISPLACED_BOUNDARY: unexpected top-level directory apps/frontend/src/{$entry}";
    }

    if (is_file($path) && !in_array($entry, $allowedTopLevelFiles, true)) {
        $violations[] = "E_MISPLACED_BOUNDARY: unexpected top-level file apps/frontend/src/{$entry}";
    }
}

foreach (glob($frontendRoot . '/routes/*/*') ?: [] as $routeDir) {
    if (!is_dir($routeDir)) {
        continue;
    }

    $routeFile = $routeDir . '/[id].tsx';
    $contractFile = $routeDir . '/routeContract.ts';
    enforcePair($routeFile, $contractFile, $violations);
}

foreach (glob($frontendRoot . '/components/aggregates/*') ?: [] as $aggregateDir) {
    if (!is_dir($aggregateDir)) {
        continue;
    }

    $contractFile = $aggregateDir . '/componentContract.ts';
    $detailViews = glob($aggregateDir . '/*DetailView.tsx') ?: [];
    $decisionPanels = glob($aggregateDir . '/DecisionPanel.tsx') ?: [];
    $sources = array_merge($detailViews, $decisionPanels);

    if ($sources === []) {
        $violations[] = 'E_ORPHAN_CONTRACT: ' . relativeToRoot($root, $contractFile) . ' has no aggregate source';
        continue;
    }

    if (count($sources) > 1) {
        $violations[] = 'E_MISPLACED_BOUNDARY: ' . relativeToRoot($root, $aggregateDir) . ' contains multiple aggregate sources';
    }

    enforcePair($sources[0], $contractFile, $violations);
}

$primitiveRoot = $frontendRoot . '/components/primitives';
foreach (scandir($primitiveRoot) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    if (!in_array($entry, $allowedPrimitiveFiles, true)) {
        $violations[] = "E_MISPLACED_BOUNDARY: unexpected primitive file components/primitives/{$entry}";
    }
}

$stateRoot = $frontendRoot . '/state';
foreach (scandir($stateRoot) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }

    if (!in_array($entry, $allowedStateFiles, true)) {
        $violations[] = "E_MISPLACED_BOUNDARY: unexpected state file state/{$entry}";
    }
}

$actionsRoot = $frontendRoot . '/actions';
foreach (scandir($actionsRoot) ?: [] as $context) {
    if ($context === '.' || $context === '..') {
        continue;
    }

    $contextPath = $actionsRoot . '/' . $context;
    if (is_file($contextPath)) {
        if ($context !== 'renderActionBinding.ts') {
            $violations[] = "E_MISPLACED_BOUNDARY: unexpected action root file actions/{$context}";
        }
        continue;
    }

    if (!in_array($context, $allowedActionContexts, true)) {
        $violations[] = "E_MISPLACED_BOUNDARY: unexpected action context actions/{$context}";
        continue;
    }

    foreach (glob($contextPath . '/*') ?: [] as $actionDir) {
        if (!is_dir($actionDir)) {
            $violations[] = 'E_MISPLACED_BOUNDARY: unexpected file ' . relativeToRoot($root, $actionDir);
            continue;
        }

        $actionName = basename($actionDir);
        $source = $actionDir . '/' . $actionName . 'Action.ts';
        $contract = $actionDir . '/actionContract.ts';
        enforcePair($source, $contract, $violations);
    }
}

if ($violations !== []) {
    fwrite(STDERR, "Frontend filesystem lint failed.\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }
    exit(1);
}

fwrite(STDOUT, "Frontend filesystem lint passed.\n");

function enforcePair(string $source, string $contract, array &$violations): void
{
    if (!is_file($source) && is_file($contract)) {
        $violations[] = 'E_ORPHAN_CONTRACT: ' . basename($contract) . ' exists without corresponding source at ' . relativeToRootGlobal($source);
        return;
    }

    if (is_file($source) && !is_file($contract)) {
        $violations[] = 'E_ORPHAN_SOURCE: ' . relativeToRootGlobal($source) . ' exists without adjacent contract';
        return;
    }
}

function relativeToRoot(string $root, string $path): string
{
    if (str_starts_with($path, $root . '/')) {
        return substr($path, strlen($root) + 1);
    }

    return $path;
}

function relativeToRootGlobal(string $path): string
{
    $root = realpath(__DIR__ . '/../../../..');
    if ($root !== false && str_starts_with($path, $root . '/')) {
        return substr($path, strlen($root) + 1);
    }

    return $path;
}
