#!/usr/bin/env php
<?php

declare(strict_types=1);

final class BlueprintCompiler
{
    private string $root;
    private string $manifestsRoot;
    private string $outputRoot;
    private string $frontendRoot;
    /** @var list<string> */
    private array $violations = [];
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(string $repoRoot)
    {
        $this->root = rtrim($repoRoot, '/');
        $this->manifestsRoot = $this->root . '/packages/ui/manifests';
        $this->outputRoot = $this->root . '/.blueprint';
        $this->frontendRoot = $this->root . '/apps/frontend/src';
    }

    public function compile(): int
    {
        $registry = $this->loadRegistry();
        $this->validateStructuralBinding();
        $reports = $this->buildReports($registry);

        if (!is_dir($this->outputRoot)) {
            mkdir($this->outputRoot, 0777, true);
        }

        file_put_contents(
            $this->outputRoot . '/compliance.json',
            json_encode($reports, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL
        );

        $log = [];
        foreach ($this->violations as $violation) {
            $log[] = '[HARD] ' . $violation;
        }
        foreach ($this->warnings as $warning) {
            $log[] = '[WARN] ' . $warning;
        }

        file_put_contents($this->outputRoot . '/violations.log', implode(PHP_EOL, $log) . PHP_EOL);

        if ($this->violations !== []) {
            fwrite(STDERR, "Blueprint compiler failed.\n");
            foreach ($this->violations as $violation) {
                fwrite(STDERR, " - {$violation}\n");
            }

            return 1;
        }

        fwrite(STDOUT, "Blueprint compiler passed.\n");
        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadRegistry(): array
    {
        return [
            'aggregates' => $this->readJson($this->manifestsRoot . '/registry/core-aggregates.json'),
            'roles' => $this->readJson($this->manifestsRoot . '/registry/core-roles.json'),
            'aggregateContracts' => $this->loadContracts($this->manifestsRoot . '/aggregates'),
            'roleContracts' => $this->loadContracts($this->manifestsRoot . '/roles'),
            'componentContracts' => $this->loadContracts($this->manifestsRoot . '/components'),
            'routeContracts' => $this->loadContracts($this->manifestsRoot . '/routes'),
            'actionContracts' => $this->loadContracts($this->manifestsRoot . '/actions'),
        ];
    }

    private function validateStructuralBinding(): void
    {
        $routeFiles = $this->collectBusinessFiles($this->frontendRoot . '/routes', 'tsx', '/\/\[id\]\.tsx$/');
        $componentFiles = $this->collectBusinessFiles($this->frontendRoot . '/components/aggregates', 'tsx', '/\/[^\/]+(?:DetailView|DecisionPanel)\.tsx$/');
        $actionFiles = $this->collectBusinessFiles($this->frontendRoot . '/actions', 'ts', '/\/[A-Z][A-Za-z0-9]+Action\/[A-Z][A-Za-z0-9]+Action\.ts$/');

        $this->enforceAdjacency($routeFiles, 'routeContract.ts', 'E_ORPHAN_SOURCE');
        $this->enforceAdjacency($componentFiles, 'componentContract.ts', 'E_ORPHAN_SOURCE');
        $this->enforceAdjacency($actionFiles, 'actionContract.ts', 'E_ORPHAN_SOURCE');

        $this->enforceContractConsumption($this->frontendRoot . '/routes', 'routeContract.ts', '/\.\/routeContract/', 'E_ORPHAN_CONTRACT');
        $this->enforceContractConsumption($this->frontendRoot . '/components/aggregates', 'componentContract.ts', '/\.\/componentContract/', 'E_ORPHAN_CONTRACT');
        $this->enforceContractConsumption($this->frontendRoot . '/actions', 'actionContract.ts', '/\.\/actionContract/', 'E_ORPHAN_CONTRACT');
    }

    /**
     * @param array<string, mixed> $registry
     * @return array<string, mixed>
     */
    private function buildReports(array $registry): array
    {
        foreach ($registry['aggregates'] as $aggregate) {
            $this->requireAggregateContract($aggregate, $registry['aggregateContracts']);
            $this->requireAggregateRouteSurface($aggregate, $registry['routeContracts']);
            $this->requireAggregateComponentSurface($aggregate, $registry['componentContracts']);
            $this->requireAggregateActionSurface($aggregate, $registry['actionContracts']);
        }

        foreach ($registry['roles'] as $role) {
            if (!$this->hasRoleContract($role, $registry['roleContracts'])) {
                $this->violate('E_ROLE_CONTRACT_MISSING', "Missing role contract for {$role}");
            }
        }

        $routeCoverage = $this->reconcileRouteContracts($registry['routeContracts']);
        $componentCoverage = $this->reconcileComponentContracts($registry['componentContracts']);
        $actionCoverage = $this->reconcileActionContracts($registry['actionContracts']);

        return [
            'generatedAt' => gmdate(DATE_ATOM),
            'hardFailures' => $this->violations,
            'warnings' => $this->warnings,
            'aggregateCoverage' => count($registry['aggregateContracts']),
            'roleCoverage' => count($registry['roleContracts']),
            'routeCoverage' => count($registry['routeContracts']),
            'componentCoverage' => count($registry['componentContracts']),
            'actionCoverage' => count($registry['actionContracts']),
            'sourceStatus' => [
                'frontendSourcePresent' => is_dir($this->frontendRoot),
                'frontendRoot' => $this->relativeToRoot($this->frontendRoot),
                'routeContractsBound' => count($routeCoverage),
                'componentContractsBound' => count($componentCoverage),
                'actionContractsBound' => count($actionCoverage),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     */
    private function requireAggregateContract(string $aggregate, array $contracts): void
    {
        foreach ($contracts as $contract) {
            if (($contract['aggregate'] ?? null) === $aggregate) {
                return;
            }
        }

        $this->violate('E_AGGREGATE_CONTRACT_MISSING', "Missing aggregate contract for {$aggregate}");
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     */
    private function requireAggregateRouteSurface(string $aggregate, array $contracts): void
    {
        foreach ($contracts as $contract) {
            if (($contract['aggregate'] ?? null) !== $aggregate) {
                continue;
            }

            $route = (string) ($contract['route'] ?? '');
            $source = $this->frontendRoot . '/' . $this->routeToSourcePath($route);
            if (is_file($source)) {
                return;
            }
        }

        $this->violate('E_UNRENDERED_AGGREGATE', "Aggregate {$aggregate} has no route materialization");
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     */
    private function requireAggregateComponentSurface(string $aggregate, array $contracts): void
    {
        foreach ($contracts as $contract) {
            if (($contract['aggregate'] ?? null) !== $aggregate) {
                continue;
            }

            $source = $this->frontendRoot . '/' . $this->componentToSourcePath($aggregate, (string) ($contract['component'] ?? ''));
            if (is_file($source)) {
                return;
            }
        }

        $this->violate('E_UNRENDERED_AGGREGATE', "Aggregate {$aggregate} has no aggregate detail surface");
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     */
    private function requireAggregateActionSurface(string $aggregate, array $contracts): void
    {
        foreach ($contracts as $contract) {
            if (($contract['aggregate'] ?? null) !== $aggregate) {
                continue;
            }

            $source = $this->frontendRoot . '/' . $this->actionToSourcePath($aggregate, (string) ($contract['action'] ?? ''));
            if (is_file($source)) {
                return;
            }
        }

        $this->violate('E_GHOST_ACTION', "Aggregate {$aggregate} declares action contracts without source modules");
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     * @return array<string, string>
     */
    private function reconcileRouteContracts(array $contracts): array
    {
        $bound = [];

        foreach ($contracts as $contract) {
            $route = (string) ($contract['route'] ?? '');
            $source = $this->frontendRoot . '/' . $this->routeToSourcePath($route);
            $bound[$route] = $source;

            if (!is_file($source)) {
                $this->violate('E_DEAD_ROUTE', "Route contract {$route} does not resolve to " . $this->relativeToRoot($source));
                continue;
            }

            $contents = file_get_contents($source);
            if ($contents === false || preg_match('/import\s+[^;]*from\s+[\'"]\.\/routeContract[\'"]/', $contents) !== 1) {
                $this->violate('E_DEAD_ROUTE', "Route source " . $this->relativeToRoot($source) . " does not bind routeContract");
            }
        }

        return $bound;
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     * @return array<string, string>
     */
    private function reconcileComponentContracts(array $contracts): array
    {
        $bound = [];

        foreach ($contracts as $contract) {
            $component = (string) ($contract['component'] ?? '');
            $aggregate = (string) ($contract['aggregate'] ?? '');
            $source = $this->frontendRoot . '/' . $this->componentToSourcePath($aggregate, $component);
            $bound[$component] = $source;

            if (!is_file($source)) {
                $this->violate('E_UNRENDERED_AGGREGATE', "Component contract {$component} does not resolve to " . $this->relativeToRoot($source));
                continue;
            }

            $contents = file_get_contents($source);
            if ($contents === false || preg_match('/import\s+[^;]*from\s+[\'"]\.\/componentContract[\'"]/', $contents) !== 1) {
                $this->violate('E_UNBOUND_COMPONENT', "Component source " . $this->relativeToRoot($source) . " does not bind componentContract");
            }
        }

        return $bound;
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     * @return array<string, string>
     */
    private function reconcileActionContracts(array $contracts): array
    {
        $bound = [];

        foreach ($contracts as $contract) {
            $action = (string) ($contract['action'] ?? '');
            $aggregate = (string) ($contract['aggregate'] ?? '');
            $source = $this->frontendRoot . '/' . $this->actionToSourcePath($aggregate, $action);
            $bound[$action] = $source;

            if (!is_file($source)) {
                $this->violate('E_GHOST_ACTION', "Action contract {$action} does not resolve to " . $this->relativeToRoot($source));
                continue;
            }

            $contents = file_get_contents($source);
            if ($contents === false || preg_match('/import\s+[^;]*from\s+[\'"]\.\/actionContract[\'"]/', $contents) !== 1) {
                $this->violate('E_GHOST_ACTION', "Action source " . $this->relativeToRoot($source) . " does not bind actionContract");
            }
        }

        return $bound;
    }

    /**
     * @param list<string> $files
     */
    private function enforceAdjacency(array $files, string $contractFile, string $code): void
    {
        foreach ($files as $file) {
            $adjacent = dirname($file) . '/' . $contractFile;
            if (!is_file($adjacent)) {
                $this->violate($code, $this->relativeToRoot($file) . " is missing adjacent {$contractFile}");
            }
        }
    }

    private function enforceContractConsumption(string $root, string $contractFile, string $pattern, string $code): void
    {
        foreach ($this->collectContractFiles($root, $contractFile) as $contractPath) {
            $used = false;
            foreach ($this->collectAdjacentBusinessFiles(dirname($contractPath), $contractFile) as $source) {
                $contents = file_get_contents($source);
                if ($contents !== false && preg_match($pattern, $contents) === 1) {
                    $used = true;
                    break;
                }
            }

            if (!$used) {
                $this->violate($code, $this->relativeToRoot($contractPath) . ' is not consumed by an adjacent business file');
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $contracts
     */
    private function hasRoleContract(string $role, array $contracts): bool
    {
        foreach ($contracts as $contract) {
            if (($contract['role'] ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadContracts(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $items = [];
        foreach (glob($dir . '/*.json') ?: [] as $file) {
            $items[] = $this->readJson($file);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|array<int, mixed>
     */
    private function readJson(string $file): array
    {
        /** @var array<string, mixed>|array<int, mixed> $decoded */
        $decoded = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    /**
     * @return list<string>
     */
    private function collectBusinessFiles(string $root, string $extension, string $pathPattern): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== $extension) {
                continue;
            }

            $path = $file->getPathname();
            if (preg_match($pathPattern, $path) === 1) {
                $files[] = $path;
            }
        }

        sort($files);
        return $files;
    }

    /**
     * @return list<string>
     */
    private function collectContractFiles(string $root, string $contractFile): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getBasename() === $contractFile) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);
        return $files;
    }

    /**
     * @return list<string>
     */
    private function collectAdjacentBusinessFiles(string $dir, string $contractFile): array
    {
        $files = [];
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..' || $entry === $contractFile) {
                continue;
            }

            $path = $dir . '/' . $entry;
            if (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), ['ts', 'tsx'], true)) {
                $files[] = $path;
            }
        }

        sort($files);
        return $files;
    }

    private function routeToSourcePath(string $route): string
    {
        $segments = array_values(array_filter(explode('/', trim($route, '/')), static fn (string $segment): bool => $segment !== ''));
        if ($segments === []) {
            return 'routes/[id].tsx';
        }

        $segments[count($segments) - 1] = '[id].tsx';
        return 'routes/' . implode('/', $segments);
    }

    private function componentToSourcePath(string $aggregate, string $component): string
    {
        if ($component === 'DecisionPanel') {
            return 'components/aggregates/DecisionSurface/DecisionPanel.tsx';
        }

        return "components/aggregates/{$aggregate}/{$aggregate}DetailView.tsx";
    }

    private function actionToSourcePath(string $aggregate, string $action): string
    {
        return 'actions/' . $this->actionContextForAggregate($aggregate) . "/{$action}/{$action}Action.ts";
    }

    private function actionContextForAggregate(string $aggregate): string
    {
        return match ($aggregate) {
            'Invoice', 'Payment' => 'accounts-payable',
            'Customer' => 'accounts-receivable',
            'StockItem', 'StockMovement' => 'inventory',
            'PurchaseRequest', 'ApprovalRequest' => 'procurement',
            'Order', 'Task' => 'order-fulfillment',
            'UserAccessGrant' => 'organization',
            'AuditEntry', 'NotificationException' => 'reporting',
            default => 'unknown',
        };
    }

    private function relativeToRoot(string $path): string
    {
        if (str_starts_with($path, $this->root . '/')) {
            return substr($path, strlen($this->root) + 1);
        }

        return $path;
    }

    private function violate(string $code, string $message): void
    {
        $this->violations[] = "{$code}: {$message}";
    }
}

$repoRoot = realpath(__DIR__ . '/../../../..');
if ($repoRoot === false) {
    fwrite(STDERR, "Could not resolve repository root.\n");
    exit(1);
}

$compiler = new BlueprintCompiler($repoRoot);
exit($compiler->compile());
