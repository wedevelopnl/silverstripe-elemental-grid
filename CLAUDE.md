# CLAUDE.md

## Project Overview

SilverStripe Elemental Grid — converts `dnadesign/silverstripe-elemental` into a grid-based content block system. **SilverStripe 6** version, ground-up rewrite on an orphaned branch.

Package: `wedevelopnl/silverstripe-elemental-grid` (type: `silverstripe-vendormodule`)

## Requirements

- PHP ^8.3
- `dnadesign/silverstripe-elemental` ^6.0, `silverstripe/framework` ^6.0, `silverstripe/admin` ^3.0, `silverstripe/vendor-plugin` ^3.0
- Conflicts with `dnadesign/silverstripe-elemental-list` (replaces its functionality)

## Architecture

```
src/                  # PHP source (PSR-4: WeDevelop\ElementalGrid\)
tests/Unit/           # PHPUnit unit tests (no DB/framework)
tests/Integration/    # PHPUnit integration tests (full SS env)
tests/E2E/            # Playwright E2E tests
tests/E2E/Fixture/    # YAML fixtures for E2E test data
tests/E2E/specs/      # E2E test specs
tests/E2E/helpers/    # Shared E2E test utilities
client/src/           # Frontend source (React/TS/SCSS) — not yet scaffolded
client/dist/          # Vite build output (exposed, created by build)
client/images/        # Static images (exposed, not yet created)
client/lang/          # Frontend translations (exposed, not yet created)
lang/                 # PHP translations (exposed, not yet created)
.docker/              # Docker dev env: Caddy + PHP + MySQL 8
```

- PSR-4 namespace: `WeDevelop\ElementalGrid\` → `src/`
- Frontend: React 18, TypeScript 5.9, Vite 7, SCSS
- Key frontend libs: dnd-kit (drag & drop), TanStack Query (data fetching), Zod (validation)
- Testing: Vitest + React Testing Library (jsdom), PHPUnit 11, Playwright (E2E)
- Node: >=24 (pinned to 24.13 in `.nvmrc`)
- Docker dev env: Caddy + PHP + MySQL 8 (see `.docker/`)

### Key Files

- `vite.config.ts` — Build config + Vitest test config, `@` alias → `client/src`
- `tsconfig.json` — TypeScript config
- `playwright.config.ts` — Playwright E2E test config (base URL from `.docker/.env` or `E2E_BASE_URL`)
- `stryker.config.mjs` — Stryker JS mutation testing config
- `Makefile` — Docker-based PHP test/coverage commands
- `.docker/compose.yml` — Docker service definitions
- `.docker/env.sh` — Generates `.docker/.env` with deterministic ports
- `.docker/app/infection.json5` — Infection mutation testing config
- `.docker/app/phpunit.mutation.xml.dist` — PHPUnit config for mutation testing
- `.docker/app/phpstan.neon.dist` — PHPStan config (level max + Silverstan + 100% type coverage)

### PHP Testing

- PHPUnit 11 — runs inside Docker via `make test`
- PHPUnit configs: `.docker/app/phpunit.unit.xml.dist` (unit), `.docker/app/phpunit.xml.dist` (integration)
- Test namespace: `WeDevelop\ElementalGrid\Tests\` → `tests/` (Unit/ + Integration/)

### Static Analysis

- PHPStan level max with Silverstan (SilverStripe-aware rules)
- 100% type coverage enforced: return, param, property, constant, declare
- Runs inside Docker via `make analyse`

## Code Style

- 4 spaces: PHP, `composer.json`
- 2 spaces: YML, JS, TS, TSX, JSON, CSS, SCSS (enforced via `.editorconfig`)
- LF line endings, UTF-8, trailing newline

## Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Vite production build |
| `npm run dev` | Vite watch mode for development |
| `npm run test` | Run Vitest tests |
| `npm run lint` | ESLint + Stylelint |
| `npm run lint:js` | ESLint only (no fix) |
| `npm run lint:js:fix` | ESLint with auto-fix |
| `npm run lint:css` | Stylelint only (no fix) |
| `npm run lint:css:fix` | Stylelint with auto-fix |
| `npm run typecheck` | TypeScript type checking |
| `npm run test:watch` | Vitest in watch mode |
| `npm run coverage` | Vitest with coverage report |
| `npm run mutate` | JS mutation testing (Stryker) |
| `npm run test:e2e` | Run Playwright E2E tests |
| `npm run test:e2e:ui` | Playwright with interactive UI |
| `npm run test:e2e:debug` | Playwright in debug mode |
| `npm run qa` | Full QA: lint + typecheck + test |

### PHP (via Makefile — requires Docker)

| Command | Description |
|---------|-------------|
| `make up` | Start Docker services (build if needed) |
| `make down` | Stop Docker services |
| `make destroy` | Stop services and remove volumes |
| `make build` | Build Docker images without starting |
| `make test` | Run all tests (PHP unit + integration + JS) |
| `make test-unit` | Run PHP unit tests (no database/framework) |
| `make test-integration` | Run PHP integration tests (full SilverStripe env) |
| `make test-js` | Run JavaScript tests (Vitest, no Docker needed) |
| `make coverage` | Merged PHP coverage report (HTML + Clover) |
| `make coverage-unit` | PHP unit test coverage only |
| `make coverage-integration` | PHP integration test coverage only |
| `make coverage-js` | JavaScript test coverage (Vitest) |
| `make mutate` | PHP mutation testing (Infection) |
| `make mutate-js` | JS mutation testing (Stryker) |
| `make analyse` | Run PHPStan static analysis |
| `make test-e2e` | Run Playwright E2E tests (requires Docker) |
| `make test-e2e-ui` | Playwright E2E with interactive UI |
| `make qa` | Full QA suite (PHPStan + PHP tests + JS QA) |
| `make qa-js` | JavaScript QA (lint + typecheck + test) |

## Docker Dev Environment

- Run `.docker/env.sh` to generate `.docker/.env` with auto-assigned ports
- Ports are deterministic per worktree directory name (hashed)
- Default admin credentials: `admin`/`admin`
- Use `make up`/`make down` to manage services (see Commands above)

## Gotchas

- **Early stage**: `client/src/` has only a `tests/` subdirectory — no frontend source code scaffolded yet
- This is a **ground-up rewrite** for SS6 — do not copy SS5 patterns blindly from `main`/`master` branches
- The SS5 version lives on `main` (and legacy `master`) for architectural reference only
- Active development happens on branch `6` (orphaned from `main`)
- composer.json is intentionally minimal; dependencies will be added incrementally
- `make test-js` and `make coverage-js` run locally (no Docker), unlike PHP targets
- **No ESLint/Stylelint configs yet**: `eslint.config.*` and `stylelint.config.*` don't exist — lint commands will fail until these are scaffolded
- **E2E tests are opt-in**: `make test-e2e` is NOT part of `make test` or `make qa` — E2E tests require running Docker services and are slow
- **E2E fixtures**: loaded via HTTP (`/dev/elemental-grid-fixtures/{load,reset}`), gated to dev environment only
- **E2E TypeScript**: `tests/E2E/` has its own `tsconfig.json` (no vitest globals, includes Playwright types)
