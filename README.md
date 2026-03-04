# SilverStripe Elemental Grid

## Introduction

This module converts [silverstripe-elemental](https://github.com/silverstripe/silverstripe-elemental) into a grid-based content block system, enabling structured Section → Row → Column layouts with configurable CSS framework adapters (Bootstrap, Tailwind, Bulma).

> **Note**: This is a ground-up rewrite for SilverStripe 6, developed on the orphaned `6` branch. The `main` branch contains the legacy SS5 version for architectural reference only — do not base new work on it.

## Requirements

* PHP ^8.3
* silverstripe/framework ^6.0
* dnadesign/silverstripe-elemental ^6.0
* silverstripe/admin ^3.0
* silverstripe/vendor-plugin ^3.0
* Node >=24 (for frontend build)

> **Conflict**: This module conflicts with `dnadesign/silverstripe-elemental-list` and replaces its functionality.

## Installation

```
composer require wedevelopnl/silverstripe-elemental-grid
```

## Development

### Prerequisites

* Docker (for PHP tests and dev environment)
* Node >=24 (see `.nvmrc`)
* Composer

### Setup

```bash
composer install
.docker/env.sh          # Generate .docker/.env with auto-assigned ports
make up                  # Start Docker services
npm install
npm run build            # Vite production build
```

### Testing

| Command | Description |
|---------|-------------|
| `make test` | Run all PHP tests (unit + integration) |
| `make test-unit` | PHP unit tests only (no database/framework) |
| `make test-integration` | PHP integration tests (full SilverStripe env) |
| `npm run test` | Run JavaScript tests (Vitest) |
| `make test-e2e` | Run Playwright E2E tests (requires running Docker services) |

### Quality

| Command | Description |
|---------|-------------|
| `make analyse` | PHPStan static analysis (level max) |
| `npm run lint` | ESLint + Stylelint |
| `npm run typecheck` | TypeScript type checking |
| `make qa` | Full QA suite (PHPStan + PHP tests + JS QA) |

### Issue Tracking with Beads

This project uses [Beads](https://github.com/steveyegge/beads) for issue tracking. Beads is a git-backed issue tracker that lives in the repository, designed for AI-assisted development workflows.

#### Quick Start

Install beads (one-time setup):

```bash
# Pick one:
npm install -g @beads/bd
brew install beads
go install github.com/steveyegge/beads/cmd/bd@latest
```

For other installation methods, see the [official documentation](https://github.com/steveyegge/beads).

Common commands:

```bash
bd ready                              # Find issues ready to work on
bd show <id>                          # View issue details
bd update <id> --status=in_progress   # Claim an issue
bd close <id>                         # Mark issue as done
bd sync                               # Sync issues with git remote
```

Issues are stored in `.beads/` and committed alongside code. No external services or web UIs needed.

## License

See [License](LICENSE)

## Maintainers

* [WeDevelop](https://www.wedevelop.nl/) <development@wedevelop.nl>

## Development and contribution

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
