import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..", "..", "..");
const args = new Map(process.argv.slice(2).map((arg) => {
  const [key, value = "true"] = arg.split("=");
  return [key, value];
}));

const manifestPath = path.resolve(repoRoot, args.get("--manifest") ?? "apps/frontend/semantic-manifest.json");
const runtimeSemanticHash = process.env.EPSILON_FRONTEND_SEMANTIC_HASH ?? "";
const runtimeImageHash = process.env.EPSILON_FRONTEND_IMAGE_HASH ?? "";
const expectedImageHash = process.env.EPSILON_EXPECTED_IMAGE_HASH ?? "";

if (!fs.existsSync(manifestPath)) {
  console.error(JSON.stringify({ status: "failed", code: "E_RUNTIME_MANIFEST_MISSING", manifestPath }, null, 2));
  process.exit(1);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
const failures = [];

if (runtimeSemanticHash !== "" && runtimeSemanticHash !== manifest.semanticHash) {
  failures.push({
    code: "E_RUNTIME_HASH_DRIFT",
    expected: manifest.semanticHash,
    actual: runtimeSemanticHash,
  });
}

if (expectedImageHash !== "" && runtimeImageHash !== "" && expectedImageHash !== runtimeImageHash) {
  failures.push({
    code: "E_RUNTIME_IMAGE_DRIFT",
    expected: expectedImageHash,
    actual: runtimeImageHash,
  });
}

if (manifest.violationCounts.ast > 0 || manifest.violationCounts.compile > 0) {
  failures.push({
    code: "E_RUNTIME_VIOLATION_DIRTY",
    astViolations: manifest.violationCounts.ast,
    compileViolations: manifest.violationCounts.compile,
  });
}

if (failures.length > 0) {
  console.error(JSON.stringify({ status: "failed", semanticHash: manifest.semanticHash, failures }, null, 2));
  process.exit(1);
}

console.log(JSON.stringify({
  status: "ok",
  semanticHash: manifest.semanticHash,
  runtimeImageHash,
  verifyEndpoint: manifest.runtime.verifyEndpoint,
}, null, 2));
