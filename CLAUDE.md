# CLAUDE.md

## Project Overview

SilverStripe Elemental Grid — converts `dnadesign/silverstripe-elemental` into a grid-based content block system. **SilverStripe 6** version, ground-up rewrite on an orphaned branch.

Package: `wedevelopnl/silverstripe-elemental-grid` (type: `silverstripe-vendormodule`)

## Code Style

- 4 spaces: PHP, `composer.json`
- 2 spaces: YML, JS, JSON, CSS, SCSS
- LF line endings, UTF-8, trailing newline

## Gotchas

- This is a **ground-up rewrite** for SS6 — do not copy SS5 patterns blindly from `main`/`master` branches
- The SS5 version lives on `main` (and legacy `master`) for architectural reference only
- composer.json is intentionally minimal; dependencies will be added incrementally
