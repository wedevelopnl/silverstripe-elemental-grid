# SilverStripe Elemental Grid

## Introduction

This module converts the elemental module (https://github.com/silverstripe/silverstripe-elemental) into a grid module.

## Requirements

* PHP ^8.3
* silverstripe/framework ^6.0
* dnadesign/silverstripe-elemental ^6.0

## Installation

```
composer require wedevelopnl/silverstripe-elemental-grid
```

## Development

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
