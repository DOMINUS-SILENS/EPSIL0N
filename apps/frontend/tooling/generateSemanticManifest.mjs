import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..", "..", "..");
const frontendRoot = path.join(repoRoot, "apps/frontend");
const blueprintRoot = path.join(repoRoot, ".blueprint");
const astViolationsFile = path.join(blueprintRoot, "ast-violations.json");
const complianceFile = path.join(blueprintRoot, "compliance.json");
const manifestFile = path.join(frontendRoot, "semantic-manifest.json");
const blueprintManifestFile = path.join(blueprintRoot, "frontend-semantic-manifest.json");

const sourceFiles = collectFiles(path.join(frontendRoot, "src"), (file) => file.endsWith(".ts") || file.endsWith(".tsx"));
const manifestInputs = [
  relativeFileHash(path.join(frontendRoot, "package-lock.json")),
  relativeFileHash(path.join(frontendRoot, "package.json")),
  relativeFileHash(path.join(frontendRoot, "tsconfig.json")),
  relativeFileHash(astViolationsFile),
  relativeFileHash(complianceFile),
  ...sourceFiles.map((file) => relativeFileHash(file)),
];

const semanticHash = crypto.createHash("sha256").update(manifestInputs.join("\n")).digest("hex");
const astViolations = readJson(astViolationsFile);
const compliance = readJson(complianceFile);
const astViolationCounts = countViolationCodes(astViolations.violations);
const compileViolationCounts = countViolationCodes(compliance.hardFailures);

const manifest = {
  generatedAt: new Date().toISOString(),
  semanticHash,
  runtime: {
    verifyEndpoint: "/health/contract-verify",
    metricsEndpoint: "/metrics",
  },
  frontendRoot: "apps/frontend/src",
  sourceFiles: sourceFiles.map((file) => path.relative(repoRoot, file)),
  contractArtifacts: {
    astViolations: path.relative(repoRoot, astViolationsFile),
    compliance: path.relative(repoRoot, complianceFile),
  },
  violationCounts: {
    ast: astViolations.violations.length,
    compile: compliance.hardFailures.length,
  },
  violationCodeCounts: {
    ast: astViolationCounts,
    compile: compileViolationCounts,
  },
};

fs.writeFileSync(manifestFile, `${JSON.stringify(manifest, null, 2)}\n`);
fs.mkdirSync(blueprintRoot, { recursive: true });
fs.writeFileSync(blueprintManifestFile, `${JSON.stringify(manifest, null, 2)}\n`);

console.log(`Generated semantic manifest: ${path.relative(repoRoot, manifestFile)}`);

function collectFiles(root, predicate) {
  const files = [];
  walk(root, files, predicate);
  return files.sort();
}

function walk(dir, files, predicate) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      walk(fullPath, files, predicate);
      continue;
    }
    if (predicate(fullPath)) {
      files.push(fullPath);
    }
  }
}

function relativeFileHash(file) {
  const contents = fs.existsSync(file) ? fs.readFileSync(file, "utf8") : "";
  const hash = crypto.createHash("sha256").update(contents).digest("hex");
  return `${path.relative(repoRoot, file)}:${hash}`;
}

function readJson(file) {
  return JSON.parse(fs.readFileSync(file, "utf8"));
}

function countViolationCodes(violations) {
  return violations.reduce((counts, violation) => {
    const code = String(violation.code ?? "UNKNOWN");
    counts[code] = (counts[code] ?? 0) + 1;
    return counts;
  }, {});
}
