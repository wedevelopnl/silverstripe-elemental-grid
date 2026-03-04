---
description: PHP conventions including SilverStripe DI patterns and PHPStan quirks
applyTo: "**/*.php"
---

# PHP Conventions

## SilverStripe Dependency Injection

- **Property injection via `$dependencies`**: Controllers (`AdminController` subclasses) and Elements (`DataObject` subclasses) cannot use constructor injection — the framework instantiates them without DI args. Use `private static array $dependencies` for property injection instead. See `ElementalGridController` and `ElementSection`/`ElementRow`/`ElementColumn` for examples.
- **Injector constructor wiring**: Injector does NOT auto-wire constructor params from YAML interface bindings. Services with constructor injection need explicit `constructor:` config in YAML (see `_config/elements.yml`).

## Result Pattern

- Service-layer validation returns `Result` objects via `Result::ok($value)` / `Result::fail($errors)` — never throws for expected validation failures.
- Used in `ReorderService`, `ElementPersistenceService`, `ReorderExecutor`, and controller response flows.
- Check with `$result->isOk()` / `$result->isFail()`, access value via `$result->getValue()`, errors via `$result->getErrors()`.

## Container Auto-Scaffolding

- `ElementSection::onAfterWrite()` auto-creates a child `ElementRow` on draft stage if none exists.
- `ElementRow::onAfterWrite()` auto-creates a child `ElementColumn` on draft stage if none exists.
- This ensures the Section→Row→Column hierarchy is always complete. Integration tests creating elements must account for these auto-created children.

## PHPStan

- **`positive-int` narrowing**: `!== 0` does not narrow `int` to `positive-int`; use `> 0` (or `<= 0` for the guard clause) instead.
