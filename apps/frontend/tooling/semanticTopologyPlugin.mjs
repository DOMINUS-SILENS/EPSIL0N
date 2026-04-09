import { execFileSync } from "node:child_process";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..", "..", "..");
const filesystemLint = path.join(repoRoot, "packages/ui/linter/bin/frontend-filesystem-lint.php");
const blueprintCompile = path.join(repoRoot, "packages/ui/compiler/bin/blueprint-compiler.php");
const semanticAst = path.join(repoRoot, "apps/frontend/tooling/semanticAstReconciler.mjs");
const semanticManifest = path.join(repoRoot, "apps/frontend/tooling/generateSemanticManifest.mjs");
const frontendTsconfig = path.join(repoRoot, "apps/frontend/tsconfig.json");

function runPhp(scriptPath) {
  execFileSync("php", [scriptPath], {
    cwd: repoRoot,
    stdio: "inherit",
  });
}

function runNode(scriptPath, args = []) {
  execFileSync("node", [scriptPath, ...args], {
    cwd: repoRoot,
    stdio: "inherit",
  });
}

export function semanticTopologyPlugin() {
  return {
    name: "epsilon-semantic-topology",
    buildStart() {
      runPhp(filesystemLint);
      runPhp(blueprintCompile);
      runNode(semanticAst, [`--tsconfig=${frontendTsconfig}`, "--type-check"]);
      runNode(semanticManifest);
    },
  };
}
