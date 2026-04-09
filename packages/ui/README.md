# EPSILON UI Contract Layer

This package area hosts executable UI truth infrastructure:

- `manifests/`
  Machine-readable presentation, role, and semantic component contracts.
- `linter/`
  Static checks for truth-protocol violations, topology violations, and incomplete component contracts.
- `mock-generator/`
  Deterministic state fixture generation from canonical enums.

These artifacts are subordinate to `blueprint-foundation/` doctrine and should evolve with it.

`apps/frontend/src` is the executable semantic target for this package area. Business-facing frontend files must be contract-bound, pass the filesystem linter first, and then pass Blueprint Compiler reconciliation before AST-level semantic reconciliation is introduced.
