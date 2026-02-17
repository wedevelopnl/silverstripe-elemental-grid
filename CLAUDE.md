# CLAUDE.md

## Project Overview

SilverStripe Elemental Grid — converts `dnadesign/silverstripe-elemental` into a grid-based content block system. **SilverStripe 6** version, ground-up rewrite on an orphaned branch.

Package: `wedevelopnl/silverstripe-elemental-grid` (type: `silverstripe-vendormodule`)

## Requirements

- PHP ^8.3
- `dnadesign/silverstripe-elemental` ^6.0, `silverstripe/framework` ^6.0, `silverstripe/admin` ^3.0, `silverstripe/vendor-plugin` ^3.0
- Conflicts with `dnadesign/silverstripe-elemental-list` (replaces its functionality)

## Architecture

- PSR-4 namespace: `WeDevelop\ElementalGrid\` → `src/`
- Exposed public dirs: `client/dist`, `client/images`, `client/lang`, `lang`
- Frontend: React 18, TypeScript 5.9, Vite 7, SCSS
- Key frontend libs: dnd-kit (drag & drop), TanStack Query (data fetching), Zod (validation)
- Testing: Vitest + React Testing Library (jsdom)
- Node: >=24 (see `.nvmrc` for pinned version)
- Docker dev env: Caddy + PHP + MySQL 8 (see `.docker/`)

## Code Style

- 4 spaces: PHP, `composer.json`
- 2 spaces: YML, JS, JSON, CSS, SCSS
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

## Docker Dev Environment

```bash
.docker/env.sh              # Generate .docker/.env with auto-assigned ports
docker compose -f .docker/compose.yml up -d   # Start services
docker compose -f .docker/compose.yml down     # Stop services
```

Ports are deterministic per worktree directory name (hashed). Default admin: `admin`/`admin`.

## Gotchas

- **Early stage**: No `src/` or `client/src/` directories exist yet — this branch is infrastructure scaffolding only
- This is a **ground-up rewrite** for SS6 — do not copy SS5 patterns blindly from `main`/`master` branches
- The SS5 version lives on `main` (and legacy `master`) for architectural reference only
- Active development happens on branch `6` (orphaned from `main`)
- composer.json is intentionally minimal; dependencies will be added incrementally
- Conflicts with `silverstripe-elemental-list` — this module replaces that functionality
