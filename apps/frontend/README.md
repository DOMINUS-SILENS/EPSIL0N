## Frontend Semantic Tree

This frontend source tree is contract-first.

Rules:

- every business-facing route file imports an adjacent `routeContract.ts`
- every aggregate-rendering component imports an adjacent `componentContract.ts`
- every user-triggerable business action imports an adjacent `actionContract.ts`
- route surfaces live under `routes/<context>/<aggregate>/[id].tsx`
- aggregate surfaces live under `components/aggregates/<Aggregate>/<Aggregate>DetailView.tsx`
- action surfaces live under `actions/<bounded-context>/<Action>/<Action>Action.ts`
- `packages/ui/compiler/bin/blueprint-compiler.php` treats this tree as the authoritative frontend binding target
- `packages/ui/linter/bin/frontend-filesystem-lint.php` fails on orphaned or misplaced topology before compilation
- `tooling/generateSemanticManifest.mjs` emits `semantic-manifest.json` for runtime verification and rollout gating
- `tooling/verifySemanticManifest.mjs` is the runtime semantic drift check used by probes and CI

This tree exists to make frontend semantics statically attributable before full AST reconciliation is added.
