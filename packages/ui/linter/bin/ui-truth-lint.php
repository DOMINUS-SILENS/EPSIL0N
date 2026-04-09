#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = $argv[1] ?? (__DIR__ . '/../../manifests');
$schemaFile = __DIR__ . '/../../manifests/component-schema.json';
$aggregateRegistryFile = __DIR__ . '/../../manifests/registry/core-aggregates.json';
$roleRegistryFile = __DIR__ . '/../../manifests/registry/core-roles.json';

if (!is_dir($root)) {
    fwrite(STDERR, "Manifest root not found: {$root}\n");
    exit(1);
}

$violations = [];
$requiredStates = ['loading', 'pending', 'accepted', 'conflicted', 'rejected', 'failed', 'stale', 'archived'];
$requiredAggregateFields = [
    'aggregate',
    'identity_fields',
    'primary_status',
    'secondary_statuses',
    'critical_metrics',
    'risk_markers',
    'supported_states',
    'required_primitives',
    'actions',
];
$requiredRoleFields = [
    'role',
    'default_landing_intents',
    'priority_alerts',
    'visible_aggregates',
    'default_audit_visibility',
    'allowed_action_clusters',
    'mobile_urgency_profile',
];
$coreAggregates = json_decode(file_get_contents($aggregateRegistryFile), true, 512, JSON_THROW_ON_ERROR);
$coreRoles = json_decode(file_get_contents($roleRegistryFile), true, 512, JSON_THROW_ON_ERROR);
$aggregateContracts = [];
$roleContracts = [];
$componentContracts = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'json') {
        continue;
    }

    if ($file->getRealPath() === realpath($schemaFile)) {
        continue;
    }

    $decoded = json_decode(file_get_contents($file->getPathname()), true);
    if (!is_array($decoded)) {
        $violations[] = $file->getPathname() . ': invalid JSON';
        continue;
    }

    if (str_contains($file->getPathname(), '/aggregates/')) {
        $aggregateContracts[$decoded['aggregate'] ?? basename($file->getFilename(), '.json')] = [
            'path' => $file->getPathname(),
            'data' => $decoded,
        ];
        foreach ($requiredAggregateFields as $field) {
            if (!array_key_exists($field, $decoded)) {
                $violations[] = $file->getPathname() . ": missing required field {$field}";
            }
        }

        foreach ($requiredStates as $state) {
            if (!in_array($state, $decoded['supported_states'] ?? [], true)) {
                $violations[] = $file->getPathname() . ": missing required supported state {$state}";
            }
        }

        if (in_array('conflicted', $decoded['supported_states'] ?? [], true) && !in_array('ConflictBanner', $decoded['required_primitives'] ?? [], true)) {
            $violations[] = $file->getPathname() . ': conflicted aggregate must require ConflictBanner';
        }

        if (array_intersect(['pending', 'accepted', 'processing', 'synced', 'stale'], $decoded['supported_states'] ?? []) !== []
            && !in_array('SyncBadge', $decoded['required_primitives'] ?? [], true)) {
            $violations[] = $file->getPathname() . ': sync-sensitive aggregate must require SyncBadge';
        }

        if (($decoded['audit_visibility_threshold'] ?? 'recoverable') !== 'none' && !in_array('AuditRail', $decoded['required_primitives'] ?? [], true)) {
            $violations[] = $file->getPathname() . ': aggregate with audit visibility must require AuditRail';
        }

        foreach (($decoded['actions'] ?? []) as $index => $action) {
            foreach (['name', 'visible_if', 'enabled_if', 'blocked_reason_if_disabled', 'policy_source', 'requires_secondary_auth', 'requires_justification', 'audit_classification', 'truth_outcomes'] as $field) {
                if (!array_key_exists($field, $action)) {
                    $violations[] = $file->getPathname() . ": action[{$index}] missing {$field}";
                }
            }
            if (($action['requires_secondary_auth'] ?? false) === true && ($action['blocked_reason_if_disabled'] ?? '') === '') {
                $violations[] = $file->getPathname() . ": action {$action['name']} requires blocked reason for secondary auth gating";
            }
        }
    }

    if (str_contains($file->getPathname(), '/roles/')) {
        $roleContracts[$decoded['role'] ?? basename($file->getFilename(), '.json')] = [
            'path' => $file->getPathname(),
            'data' => $decoded,
        ];
        foreach ($requiredRoleFields as $field) {
            if (!array_key_exists($field, $decoded)) {
                $violations[] = $file->getPathname() . ": missing required field {$field}";
            }
        }
    }

    if (str_contains($file->getPathname(), '/components/')) {
        $componentContracts[$decoded['aggregate'] ?? $decoded['component'] ?? basename($file->getFilename(), '.json')] = [
            'path' => $file->getPathname(),
            'data' => $decoded,
        ];
        foreach (['component', 'aggregate', 'supports_states', 'primitives_used', 'truth_protocol_bindings', 'role_support'] as $field) {
            if (!array_key_exists($field, $decoded)) {
                $violations[] = $file->getPathname() . ": missing required field {$field}";
            }
        }

        foreach ($requiredStates as $state) {
            if (!in_array($state, $decoded['supports_states'] ?? [], true) && ($decoded['aggregate'] ?? '') !== 'AuditEntry') {
                $violations[] = $file->getPathname() . ": missing required component state {$state}";
            }
        }

        foreach (['202', '200', '409', '422'] as $code) {
            if (!array_key_exists($code, $decoded['truth_protocol_bindings'] ?? [])) {
                $violations[] = $file->getPathname() . ": missing truth binding for {$code}";
            }
        }
    }

    foreach (['saved', 'success', 'done'] as $banned) {
        if (json_encode($decoded, JSON_THROW_ON_ERROR) !== false && str_contains(strtolower(json_encode($decoded, JSON_THROW_ON_ERROR)), '"' . $banned . '"')) {
            $violations[] = $file->getPathname() . ": banned generic truth label '{$banned}'";
        }
    }
}

foreach ($coreAggregates as $aggregate) {
    if (!array_key_exists($aggregate, $aggregateContracts)) {
        $violations[] = "AggregateContractCoverageRule: missing aggregate contract for {$aggregate}";
        continue;
    }

    if (!array_key_exists($aggregate, $componentContracts)) {
        $violations[] = "AggregateContractCoverageRule: missing component contract for {$aggregate}";
        continue;
    }

    $aggregateData = $aggregateContracts[$aggregate]['data'];
    $componentData = $componentContracts[$aggregate]['data'];

    foreach (($aggregateData['required_primitives'] ?? []) as $primitive) {
        if (!in_array($primitive, $componentData['primitives_used'] ?? [], true)) {
            $violations[] = $componentContracts[$aggregate]['path'] . ": required primitive {$primitive} missing for aggregate {$aggregate}";
        }
    }

    foreach (($aggregateData['actions'] ?? []) as $action) {
        if (!empty($action['requires_secondary_auth']) && empty($action['requires_justification'])) {
            $violations[] = $aggregateContracts[$aggregate]['path'] . ": action {$action['name']} uses secondary auth without justification contract";
        }
    }
}

foreach ($coreRoles as $role) {
    if (!array_key_exists($role, $roleContracts)) {
        $violations[] = "RoleAttentionBindingRule: missing role contract for {$role}";
    }
}

foreach ($componentContracts as $contract) {
    foreach (($contract['data']['role_support'] ?? []) as $role) {
        if (!array_key_exists($role, $roleContracts)) {
            $violations[] = $contract['path'] . ": component references undeclared role {$role}";
        }
    }

    if (in_array('DecisionPanel', $contract['data']['primitives_used'] ?? [], true) && !array_key_exists('422', $contract['data']['truth_protocol_bindings'] ?? [])) {
        $violations[] = $contract['path'] . ': DecisionPanel usage requires corrective 422 binding';
    }
}

if ($violations !== []) {
    fwrite(STDERR, "UI Truth Linter Violations:\n");
    foreach ($violations as $violation) {
        fwrite(STDERR, " - {$violation}\n");
    }

    exit(1);
}

fwrite(STDOUT, "UI truth linter passed.\n");
