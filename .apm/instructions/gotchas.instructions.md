---
description: Important gotchas and caveats to avoid common mistakes
applyTo: "**/*"
---

# Gotchas

- This is a **ground-up rewrite** for SS6 — do not copy SS5 patterns blindly from `main`/`master` branches
- The SS5 version lives on `main` (and legacy `master`) for architectural reference only
- Active development happens on branch `6` (orphaned from `main`)
- composer.json is intentionally minimal; dependencies will be added incrementally
- `make test-js` and `make coverage-js` run locally (no Docker), unlike PHP targets
- **No ESLint/Stylelint configs yet**: `eslint.config.*` and `stylelint.config.*` don't exist — lint commands will fail until these are scaffolded
- **E2E tests are opt-in**: `make test-e2e` is NOT part of `make test` or `make qa` — E2E tests require running Docker services and are slow
- **E2E fixtures**: loaded via HTTP (`/dev/grid-fixtures/{load,reset}`), gated to dev environment only
- **E2E TypeScript**: `tests/E2E/` has its own `tsconfig.json` (no vitest globals, includes Playwright types)
- **Polymorphic parent ID collisions**: page IDs and element IDs share the same numeric space — lookup maps must key by composite `"ParentClass:ParentID"` not just ParentID
