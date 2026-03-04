---
description: PHP conventions including SilverStripe DI patterns and PHPStan quirks
applyTo: "**/*.php"
---

# PHP Conventions

## SilverStripe Dependency Injection

- **Controller DI**: `AdminController` subclasses cannot use constructor injection (framework calls `new $class()` with no args). Use `private static array $dependencies` for property injection instead.
- **Injector constructor wiring**: Injector does NOT auto-wire constructor params from YAML interface bindings. Services with constructor injection need explicit `constructor:` config in YAML.

## PHPStan

- **`positive-int` narrowing**: `!== 0` does not narrow `int` to `positive-int`; use `> 0` (or `<= 0` for the guard clause) instead.
