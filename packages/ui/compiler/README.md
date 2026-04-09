# Blueprint Compiler

The Blueprint Compiler is the enforcement entry point for Blueprint Foundation.

Current responsibilities:

- registry resolution
- aggregate, role, route, component, and action contract coverage checks
- structural frontend source binding under `apps/frontend/src`
- adjacency enforcement for `routeContract.ts`, `componentContract.ts`, and `actionContract.ts`
- contract import binding enforcement for business-facing route, component, and action files
- manifest-to-source reconciliation for route, component, and action contracts
- compliance artifact generation to `/.blueprint/`

Current limitation:

- AST truth reconciliation is still staged behind a later phase. The compiler now proves structural binding, but it does not yet parse JSX branches or action flow semantics.

Commands:

- `php packages/ui/compiler/bin/blueprint-compiler.php`
- `php packages/ui/linter/bin/frontend-filesystem-lint.php`
