---
name: block-service-locator
enabled: true
event: file
action: warn
conditions:
  - field: new_text
    operator: regex_match
    pattern: Injector::inst\(\)->(get|create)\(
  - field: file_path
    operator: regex_match
    pattern: src/
---

**SilverStripe DI Anti-Pattern Detected**

Do NOT use `Injector::inst()->get()` or `Injector::inst()->create()` — this is the service locator anti-pattern and produces untestable code.

**Use proper dependency injection instead:**

- **Controllers/DataObjects**: Use `private static array $dependencies` for property injection (SilverStripe instantiates these without DI args).
- **Services**: Use constructor injection with explicit YAML `constructor:` config in `_config/*.yml`.

See `ElementalGridController` for the `$dependencies` pattern and `ElementTreeBuilder` for constructor injection.

The only justified instance is `ElementPersistenceService:72` (explained in its inline comment) and `HierarchyValidationExtension` (framework hook with no DI control).
