import http from "node:http";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(__dirname, "..", "..", "..");
const manifestPath = path.join(repoRoot, "apps/frontend/semantic-manifest.json");
const port = Number(process.env.PORT ?? "4010");

const counters = {
  epsilon_contract_mismatch_count: 0,
  epsilon_ghost_actions_total: 0,
  epsilon_state_flattening_detected: 0,
};

const server = http.createServer((request, response) => {
  if (request.method === "POST" && request.url === "/health/contract-verify") {
    const result = verifyManifest();
    if (!result.ok) {
      counters.epsilon_contract_mismatch_count += 1;
      response.writeHead(503, { "Content-Type": "application/json" });
      response.end(JSON.stringify(result, null, 2));
      return;
    }

    response.writeHead(200, { "Content-Type": "application/json" });
    response.end(JSON.stringify(result, null, 2));
    return;
  }

  if (request.method === "GET" && request.url === "/metrics") {
    const lines = Object.entries(counters).map(([name, value]) => `${name} ${value}`);
    response.writeHead(200, { "Content-Type": "text/plain; version=0.0.4" });
    response.end(`${lines.join("\n")}\n`);
    return;
  }

  response.writeHead(404, { "Content-Type": "application/json" });
  response.end(JSON.stringify({ error: "Not Found" }));
});

server.listen(port, () => {
  console.log(`Semantic probe listening on :${port}`);
});

function verifyManifest() {
  if (!fs.existsSync(manifestPath)) {
    return { ok: false, code: "E_RUNTIME_MANIFEST_MISSING" };
  }

  const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
  const runtimeSemanticHash = process.env.EPSILON_FRONTEND_SEMANTIC_HASH ?? "";

  if (runtimeSemanticHash !== "" && runtimeSemanticHash !== manifest.semanticHash) {
    return {
      ok: false,
      code: "E_RUNTIME_HASH_DRIFT",
      expected: manifest.semanticHash,
      actual: runtimeSemanticHash,
    };
  }

  const astViolationCounts = manifest.violationCodeCounts?.ast ?? {};
  counters.epsilon_ghost_actions_total = Number(astViolationCounts.E_ACTION_GHOST ?? 0);
  counters.epsilon_state_flattening_detected = Number(astViolationCounts.E_STATE_FLATTENING ?? 0);

  return {
    ok: true,
    semanticHash: manifest.semanticHash,
    runtime: manifest.runtime,
  };
}
