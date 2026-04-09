import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { Node, Project, SyntaxKind, ts } from "ts-morph";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..", "..", "..");
const args = new Map(process.argv.slice(2).map((arg) => {
  const [key, value = "true"] = arg.split("=");
  return [key, value];
}));
const frontendRoot = path.join(repoRoot, "apps/frontend/src");
const manifestsRoot = path.join(repoRoot, "packages/ui/manifests");
const outputRoot = path.join(repoRoot, ".blueprint");
const cacheRoot = path.join(outputRoot, ".ts-morph-cache");
const outputFile = path.join(outputRoot, "ast-violations.json");
const cacheMetaFile = path.join(cacheRoot, "project-meta.json");
const mode = args.get("--mode") ?? "ci";
const typeCheckEnabled = args.has("--type-check");
const tsconfigPath = path.resolve(repoRoot, args.get("--tsconfig") ?? "apps/frontend/tsconfig.json");
const preCommitBlockingCodes = new Set(["E_ROUTE_MISMATCH", "E_CONTEXT_LEAK", "E_CROSS_CONTEXT_LEAK"]);

fs.mkdirSync(outputRoot, { recursive: true });
fs.mkdirSync(cacheRoot, { recursive: true });
writeCacheMetadata();

const project = new Project({
  tsConfigFilePath: tsconfigPath,
  skipAddingFilesFromTsConfig: false,
});

const routeContracts = loadManifestMap(path.join(manifestsRoot, "routes"), "route");
const aggregateContracts = loadManifestMap(path.join(manifestsRoot, "aggregates"), "aggregate");
const componentContracts = loadManifestMap(path.join(manifestsRoot, "components"), "component");
const actionContracts = loadManifestMap(path.join(manifestsRoot, "actions"), "action");

const routeFiles = collectProjectFiles("routes", (filePath) => filePath.endsWith("[id].tsx"));
const componentFiles = collectProjectFiles("components/aggregates", (filePath) => filePath.endsWith("DetailView.tsx"));
const actionFiles = collectProjectFiles("actions", (filePath) => filePath.endsWith("Action.ts"));

const importGraph = new Map();
for (const file of [...routeFiles, ...componentFiles, ...actionFiles]) {
  importGraph.set(file.getFilePath(), resolveRelativeImports(file));
}

const violations = [];
for (const file of routeFiles) {
  violations.push(...validateRouteFile(file));
}
for (const file of componentFiles) {
  violations.push(...validateComponentFile(file));
}
for (const file of actionFiles) {
  violations.push(...validateActionFile(file));
}
violations.push(...validateActionReachability(importGraph, routeFiles));

if (typeCheckEnabled) {
  for (const diagnostic of project.getPreEmitDiagnostics()) {
    const sourceFile = diagnostic.getSourceFile();
    if (!sourceFile || !sourceFile.getFilePath().startsWith(frontendRoot)) {
      continue;
    }

    const start = diagnostic.getStart();
    const line = start === undefined ? 1 : sourceFile.getLineAndColumnAtPos(start).line;
    violations.push(violation("E_TYPE_MISMATCH", sourceFile.getFilePath(), line, "type-check", diagnostic.getMessageText()));
  }
}

writeViolations(violations);

const blockingViolations = mode === "pre-commit"
  ? violations.filter((item) => preCommitBlockingCodes.has(item.code))
  : violations;

if (blockingViolations.length > 0) {
  console.error("Semantic AST reconciliation failed.");
  for (const item of blockingViolations) {
    console.error(` - ${item.code}: ${item.file}:${item.line} ${item.message}`);
  }
  process.exit(1);
}

console.log("Semantic AST reconciliation passed.");

function validateRouteFile(sourceFile) {
  const violations = [];
  const routeContract = parseAdjacentObjectLiteral(sourceFile, "routeContract.ts", "routeContract");

  if (!sourceFile.getImportDeclaration("./routeContract")) {
    violations.push(violation("E_ROUTE_MISMATCH", sourceFile.getFilePath(), 1, "import", "routeContract import missing"));
  }

  const detailViewImport = sourceFile.getImportDeclarations().find((declaration) =>
    declaration.getNamedImports().some((namedImport) => namedImport.getName().endsWith("DetailView"))
  );
  if (!detailViewImport) {
    violations.push(violation("E_ROUTE_MISMATCH", sourceFile.getFilePath(), 1, "aggregate-view", "route does not import an aggregate detail view"));
  } else {
    const importedPath = detailViewImport.getModuleSpecifierValue();
    const expectedAggregate = routeContract.aggregate ?? "";
    if (!importedPath.includes(`/components/aggregates/${expectedAggregate}/${expectedAggregate}DetailView`)) {
      violations.push(violation("E_ROUTE_MISMATCH", sourceFile.getFilePath(), detailViewImport.getStartLineNumber(), "aggregate-view", `route does not render ${expectedAggregate}DetailView`));
    }
  }

  const routeFunction = sourceFile.getFunctions().find((fn) => fn.isExported() && fn.getName()?.endsWith("DetailRoute"));
  const routeParam = routeFunction?.getParameters()[0];
  if (!routeParam || !routeParam.getTypeNode()) {
    violations.push(violation("E_PARAM_HOLLOW", sourceFile.getFilePath(), routeFunction?.getStartLineNumber() ?? 1, "params", "route parameters are untyped"));
  } else {
    const routeParamType = routeParam.getType();
    const idProperty = routeParamType.getProperty("id");
    const idDeclaration = idProperty?.getDeclarations()[0];
    const idType = idDeclaration ? idProperty.getTypeAtLocation(idDeclaration) : null;
    if (!idType || idType.getText() !== "string") {
      violations.push(violation("E_PARAM_HOLLOW", sourceFile.getFilePath(), routeParam.getStartLineNumber(), "params", "route parameter id must resolve to string"));
    }
  }

  const bindCall = sourceFile.getDescendantsOfKind(SyntaxKind.CallExpression).find((callExpression) => {
    const expression = callExpression.getExpression();
    return Node.isIdentifier(expression) && expression.getText().startsWith("bind") && expression.getText().endsWith("RouteParams");
  });
  if (!bindCall) {
    violations.push(violation("E_PARAM_HOLLOW", sourceFile.getFilePath(), routeFunction?.getStartLineNumber() ?? 1, "params-bind", "route params are not explicitly bound"));
  }

  const projectionCall = sourceFile.getDescendantsOfKind(SyntaxKind.CallExpression).find((callExpression) => {
    const expression = callExpression.getExpression();
    return Node.isIdentifier(expression) && expression.getText().startsWith("create") && expression.getText().endsWith("Projection");
  });
  if (!projectionCall) {
    violations.push(violation("E_PARAM_HOLLOW", sourceFile.getFilePath(), routeFunction?.getStartLineNumber() ?? 1, "params-projection", "route params are not bound into projection creation"));
  } else {
    const projectionArg = projectionCall.getArguments()[0];
    if (!projectionArg || projectionArg.getType().getText() !== "string") {
      violations.push(violation("E_PARAM_HOLLOW", sourceFile.getFilePath(), projectionCall.getStartLineNumber(), "params-projection", "projection factory must be called with a string id"));
    }
  }

  for (const declaration of sourceFile.getImportDeclarations()) {
    const specifier = declaration.getModuleSpecifierValue();
    const importedSource = declaration.getModuleSpecifierSourceFile();
    if (!specifier.startsWith(".")) {
      violations.push(violation("E_CONTEXT_LEAK", sourceFile.getFilePath(), declaration.getStartLineNumber(), "import", `non-relative route import ${specifier}`));
      continue;
    }
    if (!importedSource) {
      violations.push(violation("E_CONTEXT_LEAK", sourceFile.getFilePath(), declaration.getStartLineNumber(), "import", `unresolved route import ${specifier}`));
      continue;
    }
    const importedPath = importedSource.getFilePath();
    if (
      importedPath.endsWith("routeContract.ts") ||
      importedPath.endsWith("renderRouteSurface.ts") ||
      importedPath.includes("/components/aggregates/") ||
      importedPath.endsWith("/contracts/projections.ts") ||
      importedPath.includes("/state/")
    ) {
      continue;
    }
    violations.push(violation("E_CONTEXT_LEAK", sourceFile.getFilePath(), declaration.getStartLineNumber(), "import", `disallowed route import ${specifier}`));
  }

  return violations;
}

function validateComponentFile(sourceFile) {
  const violations = [];
  const aggregate = path.basename(path.dirname(sourceFile.getFilePath()));
  const componentContract = parseAdjacentObjectLiteral(sourceFile, "componentContract.ts", "componentContract");
  const aggregateContract = aggregateContracts.get(aggregate);

  if (!sourceFile.getImportDeclaration("./componentContract")) {
    violations.push(violation("E_UNBOUND_COMPONENT", sourceFile.getFilePath(), 1, "import", "componentContract import missing"));
  }

  const viewFunction = sourceFile.getFunctions().find((fn) => fn.isExported() && fn.getName()?.endsWith("DetailView"));
  const projectionBinding = viewFunction?.getParameters()[0];
  if (!projectionBinding || !Node.isObjectBindingPattern(projectionBinding.getNameNode())) {
    violations.push(violation("E_FIELD_OMISSION", sourceFile.getFilePath(), viewFunction?.getStartLineNumber() ?? 1, "projection-binding", "component must destructure typed projection props"));
  } else {
    const projectionProperty = projectionBinding.getType().getProperty("projection");
    const projectionDeclaration = projectionProperty?.getDeclarations()[0];
    const projectionType = projectionDeclaration ? projectionProperty.getTypeAtLocation(projectionDeclaration) : null;
    const projectionAlias = projectionType?.getAliasSymbol()?.getName() ?? projectionType?.getSymbol()?.getName() ?? "";
    if (projectionAlias !== `${aggregate}Projection`) {
      violations.push(violation("E_SCHEMA_DRIFT", sourceFile.getFilePath(), projectionBinding.getStartLineNumber(), "projection-type", `component props must resolve to ${aggregate}Projection`));
    }
    for (const field of [...aggregateContract.identity_fields, ...aggregateContract.critical_metrics]) {
      if (!projectionType?.getProperty(field)) {
        violations.push(violation("E_SCHEMA_DRIFT", sourceFile.getFilePath(), projectionBinding.getStartLineNumber(), "projection-type", `projection type does not expose ${field}`));
      }
    }
  }

  const identifiers = new Set(sourceFile.getDescendantsOfKind(SyntaxKind.Identifier).map((identifier) => identifier.getText()));
  for (const field of [...aggregateContract.identity_fields, ...aggregateContract.critical_metrics]) {
    if (!identifiers.has(field)) {
      violations.push(violation("E_FIELD_OMISSION", sourceFile.getFilePath(), viewFunction?.getStartLineNumber() ?? 1, "field", `component does not acknowledge ${field}`));
    }
  }

  const stateResolver = sourceFile.getFunctions().find((fn) => fn.getName()?.startsWith("resolve") && fn.getName()?.endsWith("StateBranch"));
  const switchStatement = stateResolver?.getFirstDescendantByKind(SyntaxKind.SwitchStatement);
  const coveredStates = new Set();
  if (switchStatement) {
    for (const clause of switchStatement.getCaseBlock().getClauses()) {
      if (Node.isCaseClause(clause)) {
        const expression = clause.getExpression();
        if (Node.isStringLiteral(expression)) {
          coveredStates.add(expression.getLiteralText());
        }
      }
    }
  }
  for (const state of componentContract.states ?? []) {
    if (!coveredStates.has(state)) {
      violations.push(violation("E_STATE_FLATTENING", sourceFile.getFilePath(), stateResolver?.getStartLineNumber() ?? 1, "state-branch", `component does not branch ${state}`));
    }
  }

  const primitiveBindingsVariable = sourceFile.getVariableDeclaration("primitiveBindings");
  const primitiveInitializer = unwrapArrayLiteral(primitiveBindingsVariable?.getInitializer());
  const primitiveBindings = primitiveInitializer?.getElements().filter(Node.isStringLiteral).map((element) => element.getLiteralText()) ?? [];
  for (const primitive of componentContract.primitives ?? []) {
    if (!primitiveBindings.includes(primitive)) {
      violations.push(violation("E_PRIMITIVE_VIOLATION", sourceFile.getFilePath(), primitiveBindingsVariable?.getStartLineNumber() ?? 1, "primitive-bindings", `missing primitive ${primitive}`));
    }
  }
  for (const primitive of primitiveBindings) {
    if (!(componentContract.primitives ?? []).includes(primitive)) {
      violations.push(violation("E_PRIMITIVE_VIOLATION", sourceFile.getFilePath(), primitiveBindingsVariable?.getStartLineNumber() ?? 1, "primitive-bindings", `undeclared primitive ${primitive}`));
    }
  }

  for (const declaration of sourceFile.getImportDeclarations()) {
    const importedSource = declaration.getModuleSpecifierSourceFile();
    if (!importedSource) {
      continue;
    }
    const importedPath = importedSource.getFilePath();
    if (
      importedPath.endsWith("componentContract.ts") ||
      importedPath.endsWith("renderAggregateCard.ts") ||
      importedPath.endsWith("contracts/projections.ts")
    ) {
      continue;
    }
    if (importedPath.includes("/actions/")) {
      const allowedContext = actionContextForAggregate(aggregate);
      if (!importedPath.includes(`/actions/${allowedContext}/`)) {
        violations.push(violation("E_CROSS_CONTEXT_LEAK", sourceFile.getFilePath(), declaration.getStartLineNumber(), "import", `component imports foreign action context ${declaration.getModuleSpecifierValue()}`));
      }
      continue;
    }
    if (importedPath.includes("/components/aggregates/") && !importedPath.includes(`/components/aggregates/${aggregate}/`)) {
      violations.push(violation("E_CROSS_CONTEXT_LEAK", sourceFile.getFilePath(), declaration.getStartLineNumber(), "import", `component imports foreign aggregate ${declaration.getModuleSpecifierValue()}`));
    }
  }

  return violations;
}

function validateActionFile(sourceFile) {
  const violations = [];
  const actionContract = parseAdjacentObjectLiteral(sourceFile, "actionContract.ts", "actionContract");

  if (!sourceFile.getImportDeclaration("./actionContract")) {
    violations.push(violation("E_COMMAND_HOLLOW", sourceFile.getFilePath(), 1, "import", "actionContract import missing"));
  }

  const renderCall = sourceFile.getDescendantsOfKind(SyntaxKind.CallExpression).find((callExpression) => {
    const expression = callExpression.getExpression();
    return Node.isIdentifier(expression) && expression.getText() === "renderActionBinding";
  });
  if (!renderCall) {
    violations.push(violation("E_COMMAND_HOLLOW", sourceFile.getFilePath(), 1, "render", "action does not call renderActionBinding"));
    return violations;
  }

  const bindingObject = renderCall.getArguments()[1];
  if (!bindingObject || !Node.isObjectLiteralExpression(bindingObject)) {
    violations.push(violation("E_COMMAND_HOLLOW", sourceFile.getFilePath(), renderCall.getStartLineNumber(), "binding", "action binding object missing"));
    return violations;
  }

  const commandDispatchProperty = bindingObject.getProperty("commandDispatch");
  const dispatchCall = Node.isPropertyAssignment(commandDispatchProperty)
    ? commandDispatchProperty.getInitializerIfKind(SyntaxKind.CallExpression)
    : undefined;
  const dispatchArgument = dispatchCall?.getArguments()[0];
  if (!dispatchCall || !dispatchArgument || !Node.isStringLiteral(dispatchArgument) || dispatchArgument.getLiteralText() !== actionContract.command) {
    violations.push(violation("E_COMMAND_HOLLOW", sourceFile.getFilePath(), renderCall.getStartLineNumber(), "dispatch", `action dispatch does not match ${actionContract.command}`));
  }

  const truthProperty = bindingObject.getProperty("truthOutcomeHandlers");
  const truthInitializer = Node.isPropertyAssignment(truthProperty) ? truthProperty.getInitializer() : undefined;
  if (!truthInitializer || truthInitializer.getText() !== "actionContract.truthOutcomes") {
    violations.push(violation("E_OUTCOME_GAP", sourceFile.getFilePath(), renderCall.getStartLineNumber(), "truth-outcomes", "action does not bind declared truth outcomes"));
  }
  if (Object.keys(actionContract.truthOutcomes ?? {}).length === 0) {
    violations.push(violation("E_OUTCOME_GAP", sourceFile.getFilePath(), renderCall.getStartLineNumber(), "truth-outcomes", "action contract exposes no truth outcomes"));
  }

  const gateProperty = bindingObject.getProperty("gateRequirements");
  const gateInitializer = Node.isPropertyAssignment(gateProperty) ? gateProperty.getInitializer() : undefined;
  if ((actionContract.requiresSecondaryAuth || actionContract.requiresJustification) && gateInitializer?.getText() !== "collectGateRequirements(actionContract)") {
    violations.push(violation("E_OUTCOME_GAP", sourceFile.getFilePath(), renderCall.getStartLineNumber(), "gates", "action bypasses required gate execution"));
  }

  return violations;
}

function validateActionReachability(importGraph, routeSourceFiles) {
  const reachable = new Set();
  const queue = routeSourceFiles.map((sourceFile) => sourceFile.getFilePath());

  while (queue.length > 0) {
    const current = queue.shift();
    if (!current || reachable.has(current)) {
      continue;
    }
    reachable.add(current);
    for (const target of importGraph.get(current) ?? []) {
      if (target.startsWith(frontendRoot) && !reachable.has(target)) {
        queue.push(target);
      }
    }
  }

  const violations = [];
  for (const sourceFile of actionFiles) {
    if (!reachable.has(sourceFile.getFilePath())) {
      violations.push(violation("E_ACTION_GHOST", sourceFile.getFilePath(), 1, "reachability", "action is unreachable from routable entry points"));
    }
  }
  return violations;
}

function loadManifestMap(dir, key) {
  const items = new Map();
  for (const file of fs.readdirSync(dir)) {
    if (!file.endsWith(".json")) {
      continue;
    }
    const decoded = JSON.parse(fs.readFileSync(path.join(dir, file), "utf8"));
    items.set(decoded[key], decoded);
  }
  return items;
}

function parseAdjacentObjectLiteral(sourceFile, adjacentFileName, exportName) {
  const adjacentFile = project.getSourceFileOrThrow(path.join(path.dirname(sourceFile.getFilePath()), adjacentFileName));
  const variableDeclaration = adjacentFile.getVariableDeclarationOrThrow(exportName);
  const initializer = variableDeclaration.getInitializerIfKindOrThrow(SyntaxKind.CallExpression);
  const objectLiteral = initializer.getArguments()[0];
  return objectLiteral && Node.isObjectLiteralExpression(objectLiteral) ? objectLiteralToValue(objectLiteral) : {};
}

function objectLiteralToValue(objectLiteral) {
  const result = {};
  for (const property of objectLiteral.getProperties()) {
    if (!Node.isPropertyAssignment(property)) {
      continue;
    }
    result[property.getName()] = expressionToValue(property.getInitializerOrThrow());
  }
  return result;
}

function expressionToValue(expression) {
  if (Node.isStringLiteral(expression)) {
    return expression.getLiteralText();
  }
  if (Node.isTrueLiteral(expression)) {
    return true;
  }
  if (Node.isFalseLiteral(expression)) {
    return false;
  }
  if (Node.isArrayLiteralExpression(expression)) {
    return expression.getElements().filter(Node.isExpression).map((element) => expressionToValue(element));
  }
  if (Node.isObjectLiteralExpression(expression)) {
    return objectLiteralToValue(expression);
  }
  return expression.getText();
}

function unwrapArrayLiteral(expression) {
  if (!expression) {
    return undefined;
  }
  if (Node.isArrayLiteralExpression(expression)) {
    return expression;
  }
  if (Node.isAsExpression(expression)) {
    const innerExpression = expression.getExpression();
    return Node.isArrayLiteralExpression(innerExpression) ? innerExpression : undefined;
  }
  return undefined;
}

function collectProjectFiles(relativeRoot, predicate) {
  return project.getSourceFiles()
    .filter((sourceFile) => sourceFile.getFilePath().startsWith(path.join(frontendRoot, relativeRoot)))
    .filter((sourceFile) => predicate(sourceFile.getFilePath()));
}

function resolveRelativeImports(sourceFile) {
  return sourceFile.getImportDeclarations()
    .map((declaration) => declaration.getModuleSpecifierSourceFile())
    .filter((declaration) => declaration !== undefined)
    .map((declaration) => declaration.getFilePath());
}

function actionContextForAggregate(aggregate) {
  switch (aggregate) {
    case "Invoice":
    case "Payment":
      return "accounts-payable";
    case "Customer":
      return "accounts-receivable";
    case "StockItem":
    case "StockMovement":
      return "inventory";
    case "PurchaseRequest":
    case "ApprovalRequest":
      return "procurement";
    case "Order":
    case "Task":
      return "order-fulfillment";
    case "UserAccessGrant":
      return "organization";
    case "AuditEntry":
    case "NotificationException":
      return "reporting";
    default:
      return "unknown";
  }
}

function violation(code, file, line, node, message) {
  return {
    code,
    file: path.relative(repoRoot, file),
    line,
    node,
    message: typeof message === "string" ? message : ts.flattenDiagnosticMessageText(message, "\n"),
  };
}

function writeViolations(violations) {
  fs.writeFileSync(outputFile, `${JSON.stringify({
    generatedAt: new Date().toISOString(),
    mode,
    typeCheckEnabled,
    tsconfigPath: path.relative(repoRoot, tsconfigPath),
    violations,
  }, null, 2)}\n`);
}

function writeCacheMetadata() {
  const fingerprint = crypto.createHash("sha256")
    .update(safeRead(path.join(repoRoot, "apps/frontend/tsconfig.json")))
    .update(safeRead(path.join(repoRoot, "apps/frontend/package-lock.json")))
    .update(safeRead(path.join(repoRoot, "packages/ui/manifests/registry/core-aggregates.json")))
    .update(safeRead(path.join(repoRoot, "packages/ui/manifests/registry/core-roles.json")))
    .digest("hex");

  fs.writeFileSync(cacheMetaFile, `${JSON.stringify({
    generatedAt: new Date().toISOString(),
    fingerprint,
    tsconfigPath: path.relative(repoRoot, tsconfigPath),
  }, null, 2)}\n`);
}

function safeRead(file) {
  return fs.existsSync(file) ? fs.readFileSync(file, "utf8") : "";
}
