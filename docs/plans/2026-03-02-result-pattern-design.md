# Result Pattern for Validation Flows

## Problem

Exceptions are being used for expected validation failures — an anti-pattern. Exceptions are heavy, obscure control flow, and conflate "something went wrong" with "the user gave bad input." Validation errors are expected outcomes, not exceptional situations.

## Decision

Replace exception-based validation flows with a generic `Result<T>` type. Exceptions remain for truly exceptional situations (developer misconfiguration, infrastructure failures, impossible states).

## New Classes

### `src/Model/Result.php`

Generic success/failure container.

```php
/**
 * @template T
 */
final readonly class Result
{
    /** @param T $value */
    public static function ok(mixed $value): self;

    /** @param list<ValidationError> $errors */
    public static function fail(ValidationError ...$errors): self; // requires >= 1

    public function isOk(): bool;
    public function isErr(): bool;

    /** @return T — throws LogicException on failure (programmer bug) */
    public function unwrap(): mixed;

    /** @return list<ValidationError> */
    public function errors(): array;

    /**
     * Transform the success value without unwrapping.
     * No-op on failure.
     *
     * @template U
     * @param callable(T): U $fn
     * @return Result<U>
     */
    public function map(callable $fn): self;
}
```

`unwrap()` throws `LogicException` — calling it on a failure Result is a programmer bug, not a domain error.

### `src/Model/ValidationError.php`

Structured error value object with optional field context for frontend mapping.

```php
final readonly class ValidationError
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public string $type = 'error',
    ) {}
}
```

### `src/Service/ElementPersistenceService.php`

Single translation point where SilverStripe's `ValidationException` becomes `Result`.

```php
class ElementPersistenceService
{
    public function persistNew(BaseElement $element, ?int $afterElementId): Result;
    public function persistDuplicate(BaseElement $element, int $afterElementId): Result;

    // Private: translates ValidationException → list<ValidationError>
}
```

This is the **only** place in our code that catches `ValidationException`. One boundary, one translation.

## Modifications

### `HierarchyValidationService`

Returns `Result<true>` instead of SilverStripe's `ValidationResult`. Pure domain logic, no framework coupling.

### `HierarchyValidationExtension`

Thin adapter at the framework boundary. Calls `HierarchyValidationService::validate()`, translates `Result` errors into `ValidationResult` errors for the `updateValidate()` lifecycle hook.

### `ElementalGridController`

- Replaces try/catch `ValidationException` with Result pattern matching
- New `resultToResponse(Result, int): HTTPResponse` helper
- Deletes `extractValidationMessages()` (no longer needed)
- Injects `ElementPersistenceService` via `$dependencies`
- Guard clauses (400/403/404) stay unchanged — those aren't validation

### YAML Config

Register `ElementPersistenceService` as injectable dependency.

## Deletions

Unused domain exceptions (defined with tests but never thrown in production):

- `HierarchyValidationException` + `HierarchyValidationExceptionTest`
- `ElementNotFoundException` + `ElementNotFoundExceptionTest`
- `PermissionDeniedException` + `PermissionDeniedExceptionTest`

## Kept Unchanged

- `GridDomainException` — base class for remaining exceptions
- `InvalidGridValueException` — bad YAML config (column count = -1) is a genuine developer mistake, should throw
- Controller guard clauses — 400 (bad request body), 403 (permission denied), 404 (not found) are not validation flows

## Testing

### New Tests

- `ResultTest` — ok/fail creation, isOk/isErr, unwrap success and LogicException on failure, errors() empty on success, map() transforms success / no-ops on failure, fail() requires at least one error
- `ValidationErrorTest` — construction with all fields, defaults (field=null, type='error')
- `ElementPersistenceServiceTest` — write success → Result::ok, write throws ValidationException → Result::fail with translated errors, reorder paths

### Modified Tests

- `HierarchyValidationServiceTest` — assert on `Result<true>` instead of `ValidationResult`

### Deleted Tests

- `HierarchyValidationExceptionTest`
- `ElementNotFoundExceptionTest`
- `PermissionDeniedExceptionTest`

### Integration Tests

Existing controller integration tests (e.g. `testCreateReturns422WhenValidationFails`) should pass unchanged — external behavior is the same, only internal plumbing changed.
