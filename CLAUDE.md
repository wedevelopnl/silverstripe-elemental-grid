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
client/src/           # Frontend source (React/TS/SCSS) — not yet scaffolded
client/dist/          # Vite build output (exposed via vendor-plugin)
client/images/        # Static images (exposed)
client/lang/          # Frontend translations (exposed)
lang/                 # PHP translations (exposed)
.docker/              # Docker dev env: Caddy + PHP + MySQL 8
```

- PSR-4 namespace: `WeDevelop\ElementalGrid\` → `src/`
- Frontend: React 18, TypeScript 5.9, Vite 7, SCSS
- Key frontend libs: dnd-kit (drag & drop), TanStack Query (data fetching), Zod (validation)
- Testing: Vitest + React Testing Library (jsdom), PHPUnit 11
- Node: >=24 (pinned to 24.13 in `.nvmrc`)
- Docker dev env: Caddy + PHP + MySQL 8 (see `.docker/`)

### Key Files

- `vite.config.ts` — Build config + Vitest test config, `@` alias → `client/src`
- `tsconfig.json` — TypeScript config
- `Makefile` — Docker-based PHP test/coverage commands
- `.docker/compose.yml` — Docker service definitions
- `.docker/env.sh` — Generates `.docker/.env` with deterministic ports

### PHP Testing

- PHPUnit 11 — runs inside Docker via `make test`
- PHPUnit configs: `.docker/app/phpunit.unit.xml.dist` (unit), `.docker/app/phpunit.xml.dist` (integration)
- Test namespace: `WeDevelop\ElementalGrid\Tests\` → `tests/` (Unit/ + Integration/)

## Code Style

- 4 spaces: PHP, `composer.json`
- 2 spaces: YML, JS, TS, TSX, JSON, CSS, SCSS
- LF line endings, UTF-8, trailing newline

## Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Vite production build |
| `npm run dev` | Vite watch mode for development |
| `npm run test` | Run Vitest tests |
| `npm run lint` | ESLint + Stylelint |
| `npm run lint:js:fix` | ESLint with auto-fix |
| `npm run lint:css:fix` | Stylelint with auto-fix |
| `npm run typecheck` | TypeScript type checking |
| `npm run test:watch` | Vitest in watch mode |
| `npm run coverage` | Vitest with coverage report |
| `npm run qa` | Full QA: lint + typecheck + test |

### PHP (via Makefile — requires Docker)

| Command | Description |
|---------|-------------|
| `make up` | Start Docker services (build if needed) |
| `make down` | Stop Docker services |
| `make destroy` | Stop services and remove volumes |
| `make test` | Run all tests (PHP unit + integration + JS) |
| `make test-unit` | Run PHP unit tests (no database/framework) |
| `make test-integration` | Run PHP integration tests (full SilverStripe env) |
| `make test-js` | Run JavaScript tests (Vitest, no Docker needed) |
| `make coverage` | Merged PHP coverage report (HTML + Clover) |
| `make coverage-unit` | PHP unit test coverage only |
| `make coverage-integration` | PHP integration test coverage only |
| `make coverage-js` | JavaScript test coverage (Vitest) |

## Docker Dev Environment

```bash
.docker/env.sh              # Generate .docker/.env with auto-assigned ports
docker compose -f .docker/compose.yml up -d   # Start services
docker compose -f .docker/compose.yml down     # Stop services
```

Ports are deterministic per worktree directory name (hashed). Default admin: `admin`/`admin`.

## Gotchas

- **Early stage**: `client/src/` has only a `tests/` subdirectory — no frontend source code scaffolded yet
- This is a **ground-up rewrite** for SS6 — do not copy SS5 patterns blindly from `main`/`master` branches
- The SS5 version lives on `main` (and legacy `master`) for architectural reference only
- Active development happens on branch `6` (orphaned from `main`)
- composer.json is intentionally minimal; dependencies will be added incrementally
- `make test-js` and `make coverage-js` run locally (no Docker), unlike PHP targets
