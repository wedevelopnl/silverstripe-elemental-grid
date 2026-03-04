---
name: warn-phpstan-ignore
enabled: true
event: file
action: warn
conditions:
  - field: new_text
    operator: regex_match
    pattern: @phpstan-ignore
  - field: file_path
    operator: regex_match
    pattern: \.php$
---

**PHPStan Suppression Requires Justification**

This project runs PHPStan at level max with 100% type coverage. Every `@phpstan-ignore` suppression is a type-safety hole.

**Before adding a suppression, try these alternatives in order:**

1. **Fix with proper type hints** — most errors are fixable with correct annotations (e.g., use `> 0` for `positive-int` narrowing instead of `!== 0`).
2. **Write a PHPStan stub** — for framework limitations (dynamic properties, magic methods, extension-provided fields), add a stub file in `phpstan/` instead. Stubs are the preferred solution because they teach PHPStan about the type system rather than hiding errors. See `phpstan/AdminController.stub` for an example.
3. **Only as last resort**: If neither proper types nor a stub can resolve the error, add `@phpstan-ignore` with a justification comment on the same or preceding line explaining **why** no better option exists.

**Required format when suppression is truly necessary:**
```php
// ElementalAreasExtension adds ChildArea dynamically — stub not viable because [reason]
/** @phpstan-ignore property.notFound */
```

Existing justified suppressions in `ElementSection`, `ElementRow`, `ElementColumn` all follow this pattern with explanatory comments.
