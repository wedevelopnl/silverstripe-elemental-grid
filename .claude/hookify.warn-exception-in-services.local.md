---
name: warn-exception-in-services
enabled: true
event: file
action: warn
conditions:
  - field: new_text
    operator: regex_match
    pattern: throw new .*Exception
  - field: file_path
    operator: regex_match
    pattern: src/(Service|Validation)/.*\.php$
---

**Result Pattern Violation — Use `Result::fail()` Instead of Exceptions**

This project uses the Result pattern for domain validation in services:

- `Result::ok($value)` for success
- `Result::fail($errors)` for expected validation failures

**Throwing exceptions for validation failures breaks the controller's error-handling flow** and produces 500 errors instead of structured JSON error responses.

**When to use what:**
- **Expected validation failures** (invalid input, hierarchy violations, business rule violations) → `Result::fail()`
- **Infrastructure failures** (database down, file I/O errors, impossible states) → exceptions are appropriate

Check `ReorderService`, `ElementPersistenceService`, and `ReorderExecutor` for Result pattern examples.
