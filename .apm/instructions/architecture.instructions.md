---
description: Project architecture, directory structure, key files, testing, and static analysis
applyTo: "**/*"
---

# Architecture

```
_config/              # YAML config (DI bindings, element hierarchy, grid adapter)
templates/            # SilverStripe .ss templates (element holders + form fields)
src/                  # PHP source (PSR-4: WeDevelop\ElementalGrid\)
src/Adapter/          # Grid framework adapters (Tailwind, Bootstrap, Bulma) + GridAdapterConfiguration trait
src/Contract/         # Interfaces, enums, and value objects (GridAdapterInterface, ContainerType, Viewport)
src/Controllers/      # API controllers (ElementalGridController)
src/Dev/              # Fixture loading for E2E tests (controller, loader, post-actions, result)
src/Elements/         # Element models (ElementSection, ElementRow, ElementColumn)
src/Extensions/       # SilverStripe extensions
src/Forms/            # Form field implementations
src/Model/            # DTOs and value objects (ElementNode, Result, ValidationError)
src/Service/          # Domain services (tree building, persistence, reorder, grid config)
src/Validation/       # Hierarchy validation and reorder validation
src/Exception/        # Domain exceptions
src/Repository/       # Repository interfaces + ORM implementations
tests/Unit/           # PHPUnit unit tests (no DB/framework)
tests/Integration/    # PHPUnit integration tests (full SS env)
tests/E2E/            # Playwright E2E tests
tests/E2E/Fixture/    # YAML fixtures for E2E test data
tests/E2E/specs/      # E2E test specs
tests/E2E/helpers/    # Shared E2E test utilities
client/src/           # Frontend source (React/TS/SCSS)
client/src/api/       # API client layers (client, endpoints, config, errors)
client/src/boot/      # Component registration
client/src/bridge/    # SilverStripe CMS integration (entwine, Injector)
client/src/bundles/   # Entry points
client/src/components/ # React components
client/src/hooks/     # React hooks, query keys, TanStack Query, mutations
client/src/styles/    # SCSS styles
client/src/types/     # Zod schemas, TypeScript types
client/src/utils/     # Frontend utility functions
client/src/tests/     # Frontend test files (Vitest + RTL)
client/dist/          # Vite build output (exposed, created by build)
phpstan/              # PHPStan stubs (e.g. AdminController.stub)
.docker/              # Docker dev env: Caddy + PHP + MySQL 8
```

- PSR-4 namespace: `WeDevelop\ElementalGrid\` → `src/`
- Frontend: React 18, TypeScript 5.9, Vite 7, SCSS
- Key frontend libs: dnd-kit (drag & drop), TanStack Query (data fetching), Zod (validation)
- Testing: Vitest + React Testing Library (jsdom), PHPUnit 11, Playwright (E2E)
- Node: >=24 (pinned to 24.13 in `.nvmrc`)
- Docker dev env: Caddy + PHP + MySQL 8 (see `.docker/`)

## Key Files

- `vite.config.ts` — Build config + Vitest test config, `@` alias → `client/src`
- `tsconfig.json` — TypeScript config
- `playwright.config.ts` — Playwright E2E test config (base URL from `.docker/.env` or `E2E_BASE_URL`)
- `stryker.config.mjs` — Stryker JS mutation testing config
- `Makefile` — Docker-based PHP test/coverage commands
- `.docker/compose.yml` — Docker service definitions
- `.docker/env.sh` — Generates `.docker/.env` with deterministic ports
- `.docker/app/infection.json5` — Infection mutation testing config
- `.docker/app/phpstan.neon.dist` — PHPStan config (level max + Silverstan + 100% type coverage)

## PHP Testing

- PHPUnit 11 — runs inside Docker via `make test`
- PHPUnit config: `.docker/app/phpunit.xml.dist` (defines `unit` and `integration` testsuites, selected via `--testsuite` flag)
- Test namespace: `WeDevelop\ElementalGrid\Tests\` → `tests/` (Unit/ + Integration/)

## Static Analysis

- PHPStan level max with Silverstan (SilverStripe-aware rules)
- 100% type coverage enforced: return, param, property, constant, declare
- Runs inside Docker via `make analyse`
