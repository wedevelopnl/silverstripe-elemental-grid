# Result Pattern Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace exception-based validation flows with a generic `Result<T>` type so validation errors are return values, not thrown exceptions.

**Architecture:** New `Result<T>` and `ValidationError` value objects in `src/Model/`. A new `ElementPersistenceService` wraps SilverStripe `write()` calls and translates `ValidationException` into `Result::fail()`. `HierarchyValidationService` returns `Result<true>` instead of framework `ValidationResult`. Unused domain exceptions are deleted.

**Tech Stack:** PHP 8.3, PHPUnit 11, PHPStan level max, SilverStripe 6

**Design doc:** `docs/plans/2026-03-02-result-pattern-design.md`

---

### Task 1: Create `ValidationError` value object

**Files:**
- Create: `tests/Unit/Model/ValidationErrorTest.php`
- Create: `src/Model/ValidationError.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Model\ValidationError;

#[CoversClass(ValidationError::class)]
final class ValidationErrorTest extends TestCase
{
    public function testConstructWithAllFields(): void
    {
        $error = new ValidationError(
            message: 'Width exceeds maximum.',
            field: 'width',
            type: 'warning',
        );

        $this->assertSame('Width exceeds maximum.', $error->message);
        $this->assertSame('width', $error->field);
        $this->assertSame('warning', $error->type);
    }

    public function testFieldDefaultsToNull(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertNull($error->field);
    }

    public function testTypeDefaultsToError(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertSame('error', $error->type);
    }
}
```

**Step 2: Run test to verify it fails**

Run: `make test-unit` (or `docker compose exec -T php vendor/bin/phpunit --testsuite unit --filter ValidationErrorTest`)
Expected: FAIL — class `ValidationError` not found

**Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Model;

/**
 * Structured validation error with optional field context for frontend mapping.
 */
final readonly class ValidationError
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public string $type = 'error',
    ) {
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make test-unit` (filter `ValidationErrorTest`)
Expected: PASS (3 tests)

**Step 5: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Model/ValidationError.php tests/Unit/Model/ValidationErrorTest.php
git commit -m "feat: add ValidationError value object"
```

---

### Task 2: Create `Result<T>` class

**Files:**
- Create: `tests/Unit/Model/ResultTest.php`
- Create: `src/Model/Result.php`

**Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

#[CoversClass(Result::class)]
final class ResultTest extends TestCase
{
    public function testOkIsOk(): void
    {
        $result = Result::ok('hello');

        $this->assertTrue($result->isOk());
        $this->assertFalse($result->isErr());
    }

    public function testFailIsErr(): void
    {
        $result = Result::fail(new ValidationError('bad'));

        $this->assertFalse($result->isOk());
        $this->assertTrue($result->isErr());
    }

    public function testUnwrapReturnsValueOnSuccess(): void
    {
        $result = Result::ok(42);

        $this->assertSame(42, $result->unwrap());
    }

    public function testUnwrapThrowsLogicExceptionOnFailure(): void
    {
        $result = Result::fail(new ValidationError('bad'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot unwrap a failed Result');
        $result->unwrap();
    }

    public function testErrorsReturnsEmptyListOnSuccess(): void
    {
        $result = Result::ok('value');

        $this->assertSame([], $result->errors());
    }

    public function testErrorsReturnsListOnFailure(): void
    {
        $e1 = new ValidationError('first');
        $e2 = new ValidationError('second', 'field_name');
        $result = Result::fail($e1, $e2);

        $this->assertSame([$e1, $e2], $result->errors());
    }

    public function testFailRequiresAtLeastOneError(): void
    {
        $this->expectException(\ArgumentCountError::class);

        /** @phpstan-ignore arguments.count */
        Result::fail();
    }

    public function testMapTransformsSuccessValue(): void
    {
        $result = Result::ok(5);
        $mapped = $result->map(static fn (int $v): int => $v * 2);

        $this->assertTrue($mapped->isOk());
        $this->assertSame(10, $mapped->unwrap());
    }

    public function testMapIsNoOpOnFailure(): void
    {
        $error = new ValidationError('bad');
        $result = Result::fail($error);
        $mapped = $result->map(static fn (mixed $v): string => 'should not run');

        $this->assertTrue($mapped->isErr());
        $this->assertSame([$error], $mapped->errors());
    }

    public function testOkWithNullValue(): void
    {
        $result = Result::ok(null);

        $this->assertTrue($result->isOk());
        $this->assertNull($result->unwrap());
    }
}
```

**Step 2: Run test to verify it fails**

Run: `make test-unit` (filter `ResultTest`)
Expected: FAIL — class `Result` not found

**Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Model;

/**
 * Generic success/failure container for validation flows.
 *
 * Use Result::ok($value) for success and Result::fail($errors...) for expected failures.
 * Exceptions remain for truly exceptional situations (bugs, infrastructure failures).
 *
 * @template T
 */
final readonly class Result
{
    /**
     * @param T $value
     * @param list<ValidationError> $errors
     */
    private function __construct(
        private bool $ok,
        private mixed $value,
        private array $errors,
    ) {
    }

    /**
     * @template U
     * @param U $value
     * @return self<U>
     */
    public static function ok(mixed $value): self
    {
        return new self(ok: true, value: $value, errors: []);
    }

    /**
     * @return self<never>
     */
    public static function fail(ValidationError ...$errors): self
    {
        return new self(ok: false, value: null, errors: array_values($errors));
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return !$this->ok;
    }

    /**
     * Returns the success value.
     *
     * @return T
     * @throws \LogicException If called on a failed Result (programmer bug)
     */
    public function unwrap(): mixed
    {
        if (!$this->ok) {
            throw new \LogicException('Cannot unwrap a failed Result');
        }

        return $this->value;
    }

    /** @return list<ValidationError> */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Transform the success value. No-op on failure.
     *
     * @template U
     * @param callable(T): U $fn
     * @return self<U>
     */
    public function map(callable $fn): self
    {
        if (!$this->ok) {
            /** @var self<U> */
            return $this;
        }

        return self::ok($fn($this->value));
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make test-unit` (filter `ResultTest`)
Expected: PASS (10 tests)

**Step 5: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Model/Result.php tests/Unit/Model/ResultTest.php
git commit -m "feat: add generic Result<T> type for validation flows"
```

---

### Task 3: Refactor `HierarchyValidationService` to return `Result<true>`

**Files:**
- Modify: `src/Validation/HierarchyValidatorInterface.php` (line 12: change return type)
- Modify: `src/Validation/HierarchyValidationService.php` (lines 16-54: change return type and implementation)
- Modify: `src/Validation/HierarchyValidationExtension.php` (lines 19-30: translate Result → ValidationResult)

**Step 1: Write the failing test for HierarchyValidationService**

There are currently no unit tests for this service. Create them.

Create: `tests/Unit/Validation/HierarchyValidationServiceTest.php`

This class depends heavily on SilverStripe's ORM (`BaseElement`, `Parent()`, `getOwnerPage()`, config layer). It's an integration test candidate. However, the interface change itself can be verified in a simpler way — create a unit test that checks the Result return type contract, and rely on the existing integration tests (`testCreateReturns422WhenValidationFails`, `testDuplicateReturns422WhenValidationFails`) to verify the full flow.

Since `HierarchyValidationService` uses framework APIs (`$element->Parent()`, `$element->config()`, etc.), meaningful tests must be integration tests. Skip a unit test stub; the existing controller integration tests already cover the end-to-end validation path.

**Step 2: Update the interface**

Modify `src/Validation/HierarchyValidatorInterface.php`:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Models\BaseElement;
use WeDevelop\ElementalGrid\Model\Result;

interface HierarchyValidatorInterface
{
    /** @return Result<true> */
    public function validate(BaseElement $element): Result;
}
```

**Step 3: Update `HierarchyValidationService` to return `Result<true>`**

Modify `src/Validation/HierarchyValidationService.php`:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObject;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

class HierarchyValidationService implements HierarchyValidatorInterface
{
    /** @return Result<true> */
    #[\Override]
    public function validate(BaseElement $element): Result
    {
        $parent = $element->Parent();
        if (!$parent->exists()) {
            return Result::ok(true);
        }

        $owner = $parent->getOwnerPage();
        if ($owner === null) {
            return Result::ok(true);
        }

        // Page-level: owner has ElementalPageExtension (applied to any SiteTree subclass)
        if ($owner->hasExtension(ElementalPageExtension::class)) {
            if ($element->config()->get('can_be_root') === false) {
                return Result::fail(new ValidationError(
                    message: sprintf(
                        '%s cannot be placed inside %s.',
                        $element->singular_name(),
                        $owner->singular_name(),
                    ),
                    field: 'placement',
                ));
            }

            return Result::ok(true);
        }

        if ($this->isElementAllowed($element::class, $owner)) {
            return Result::ok(true);
        }

        return Result::fail(new ValidationError(
            message: sprintf(
                '%s cannot be placed inside %s.',
                $element->singular_name(),
                $owner->singular_name(),
            ),
            field: 'placement',
        ));
    }

    /**
     * Check if element class is permitted by the owner's
     * allowed_elements / disallowed_elements config.
     * Mirrors ElementalAreasExtension::getElementalTypes() logic.
     *
     * @param class-string<BaseElement> $elementClass
     */
    private function isElementAllowed(string $elementClass, DataObject $owner): bool
    {
        $config = $owner->config();
        $stopInheritance = (bool) $config->get('stop_element_inheritance');

        $allowedElements = $stopInheritance
            ? $config->get('allowed_elements', Config::UNINHERITED)
            : $config->get('allowed_elements');

        if (is_array($allowedElements) && !in_array($elementClass, $allowedElements, true)) {
            return false;
        }

        $disallowedElements = $stopInheritance
            ? (array) $config->get('disallowed_elements', Config::UNINHERITED)
            : (array) $config->get('disallowed_elements');

        return !in_array($elementClass, $disallowedElements, true);
    }
}
```

**Step 4: Update `HierarchyValidationExtension` to translate Result → ValidationResult**

Modify `src/Validation/HierarchyValidationExtension.php`:

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Validation;

use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationResult;

/**
 * Applied to BaseElement via YAML. Delegates hierarchy validation
 * to the centralized HierarchyValidationService, then translates
 * Result errors into the framework's ValidationResult.
 *
 * @extends Extension<\DNADesign\Elemental\Models\BaseElement>
 */
class HierarchyValidationExtension extends Extension
{
    public function updateValidate(ValidationResult $result): void
    {
        /** @var HierarchyValidatorInterface $service */
        $service = Injector::inst()->get(HierarchyValidatorInterface::class);
        $serviceResult = $service->validate($this->owner);

        if ($serviceResult->isOk()) {
            return;
        }

        foreach ($serviceResult->errors() as $error) {
            $result->addError($error->message);
        }
    }
}
```

**Step 5: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 6: Run integration tests to verify behaviour is preserved**

Run: `make test-integration`
Expected: `testCreateReturns422WhenValidationFails` and `testDuplicateReturns422WhenValidationFails` still PASS

**Step 7: Commit**

```bash
git add src/Validation/HierarchyValidatorInterface.php src/Validation/HierarchyValidationService.php src/Validation/HierarchyValidationExtension.php
git commit -m "refactor: HierarchyValidationService returns Result<true> instead of ValidationResult"
```

---

### Task 4: Create `ElementPersistenceService`

**Files:**
- Create: `tests/Unit/Service/ElementPersistenceServiceTest.php`
- Create: `src/Service/ElementPersistenceService.php`
- Modify: `_config/elements.yml` (lines 44-55: add service registration)

**Step 1: Write the failing test**

The service wraps `BaseElement::write()` and `ReorderElements::reorder()` — both are framework methods. Use mocks to test the translation boundary.

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Services\ReorderElements;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;
use WeDevelop\ElementalGrid\Service\ElementPersistenceService;

#[CoversClass(ElementPersistenceService::class)]
final class ElementPersistenceServiceTest extends TestCase
{
    public function testPersistNewReturnsOkOnSuccess(): void
    {
        $element = $this->createMock(BaseElement::class);
        $element->expects($this->once())->method('write');

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertTrue($result->isOk());
    }

    public function testPersistNewReturnsFailOnValidationException(): void
    {
        $validationResult = ValidationResult::create();
        $validationResult->addError('Row cannot be placed inside Page.');
        $exception = new ValidationException($validationResult);

        $element = $this->createMock(BaseElement::class);
        $element->expects($this->once())
            ->method('write')
            ->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertTrue($result->isErr());
        $this->assertCount(1, $result->errors());
        $this->assertSame('Row cannot be placed inside Page.', $result->errors()[0]->message);
    }

    public function testPersistNewTranslatesMultipleValidationErrors(): void
    {
        $validationResult = ValidationResult::create();
        $validationResult->addError('First error.');
        $validationResult->addError('Second error.');
        $exception = new ValidationException($validationResult);

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertCount(2, $result->errors());
        $this->assertSame('First error.', $result->errors()[0]->message);
        $this->assertSame('Second error.', $result->errors()[1]->message);
    }

    public function testPersistNewFallbackMessageWhenNoValidationMessages(): void
    {
        $exception = new ValidationException('Validation failed.');

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertTrue($result->isErr());
        $this->assertCount(1, $result->errors());
        $this->assertSame('Validation failed.', $result->errors()[0]->message);
    }
}
```

Note: The `reorder` path is harder to unit test (requires `ReorderElements` which is tightly coupled to framework). The integration tests already cover that path via `testCreateReturns422WhenValidationFails`. The unit tests above verify the `ValidationException` → `Result` translation logic.

**Step 2: Run test to verify it fails**

Run: `make test-unit` (filter `ElementPersistenceServiceTest`)
Expected: FAIL — class `ElementPersistenceService` not found

**Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Services\ReorderElements;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Validation\ValidationException;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;

/**
 * Wraps SilverStripe write/reorder operations, translating
 * framework ValidationExceptions into Result failures.
 *
 * This is the single boundary where framework exceptions become domain Results.
 */
class ElementPersistenceService
{
    use Injectable;

    /**
     * Persist a new element, optionally inserting after another element.
     *
     * @return Result<BaseElement>
     */
    public function persistNew(BaseElement $element, ?int $afterElementId = null): Result
    {
        try {
            if ($afterElementId !== null) {
                $this->reorderElement($element, $afterElementId);
            } else {
                $element->write();
            }
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    /**
     * Persist a duplicated element after the original.
     *
     * @return Result<BaseElement>
     */
    public function persistDuplicate(BaseElement $element, int $afterElementId): Result
    {
        try {
            $this->reorderElement($element, $afterElementId);
        } catch (ValidationException $e) {
            return Result::fail(...$this->translateValidationException($e));
        }

        return Result::ok($element);
    }

    private function reorderElement(BaseElement $element, int $afterElementId): void
    {
        /** @var ReorderElements $reorderer */
        $reorderer = Injector::inst()->create(ReorderElements::class, $element);
        $reorderer->reorder($afterElementId);
    }

    /**
     * Translate a SilverStripe ValidationException into ValidationError list.
     *
     * @return non-empty-list<ValidationError>
     */
    private function translateValidationException(ValidationException $e): array
    {
        $messages = $e->getResult()->getMessages();

        if ($messages === []) {
            return [new ValidationError(message: 'Validation failed.')];
        }

        return array_map(
            static fn (array $msg): ValidationError => new ValidationError(
                message: $msg['message'],
                field: $msg['fieldName'] ?? null,
            ),
            $messages,
        );
    }
}
```

**Step 4: Run test to verify it passes**

Run: `make test-unit` (filter `ElementPersistenceServiceTest`)
Expected: PASS (4 tests)

**Step 5: Register in YAML**

Add to `_config/elements.yml` under the `elemental-grid-services` Injector section (after line 54):

```yaml
  WeDevelop\ElementalGrid\Service\ElementPersistenceService: ~
```

**Step 6: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 7: Commit**

```bash
git add src/Service/ElementPersistenceService.php tests/Unit/Service/ElementPersistenceServiceTest.php _config/elements.yml
git commit -m "feat: add ElementPersistenceService wrapping write() with Result"
```

---

### Task 5: Refactor `ElementalGridController` to use Result pattern

**Files:**
- Modify: `src/Controllers/ElementalGridController.php`
  - Lines 6-16: update imports
  - Lines 60-64: add `persistenceService` dependency
  - Line 66: add public property
  - Lines 126-163: refactor `apiCreate()`
  - Lines 231-271: refactor `apiDuplicate()`
  - Lines 382-391: remove `reorderElements()` (moved to persistence service)
  - Lines 399-408: remove `extractValidationMessages()`
  - Add: `resultToResponse()` helper

**Step 1: Update imports and dependencies**

Add `ElementPersistenceService` import, add to `$dependencies` array, add public property.

Remove `ValidationException` import (no longer caught here), remove `ReorderElements` and `Injector` imports (no longer used here).

**Step 2: Refactor `apiCreate()`**

Before:
```php
$newElement->ParentID = $area->ID;
$newElement->ensureSortSet();

try {
    if ($body['insertAfterElementID'] !== null) {
        $this->reorderElements($newElement, $body['insertAfterElementID']);
    } else {
        $newElement->write();
    }
} catch (ValidationException $e) {
    $this->jsonError(422, $this->extractValidationMessages($e));
}

return $this->jsonSuccess(204);
```

After:
```php
$newElement->ParentID = $area->ID;
$newElement->ensureSortSet();

$result = $this->persistenceService->persistNew($newElement, $body['insertAfterElementID']);
if ($result->isErr()) {
    return $this->resultToResponse($result);
}

return $this->jsonSuccess(204);
```

**Step 3: Refactor `apiDuplicate()`**

Before:
```php
try {
    $clone = $element->duplicate(false);
    $clone->Title = $this->generateCopyTitle($clone->Title ?? '');
    $clone->Sort = 0;
    $area->Elements()->add($clone);

    $this->reorderElements($clone, $id);
} catch (ValidationException $e) {
    $this->jsonError(422, $this->extractValidationMessages($e));
}

return $this->jsonSuccess(204);
```

After:
```php
$clone = $element->duplicate(false);
$clone->Title = $this->generateCopyTitle($clone->Title ?? '');
$clone->Sort = 0;
$area->Elements()->add($clone);

$result = $this->persistenceService->persistDuplicate($clone, $id);
if ($result->isErr()) {
    return $this->resultToResponse($result);
}

return $this->jsonSuccess(204);
```

Note: `$element->duplicate(false)` does not trigger `write()`, it clones in memory. The `add()` call is what writes. Actually, review SilverStripe source — `duplicate()` may call `write()`. If so, that `duplicate()` + `add()` block may need to move inside the persistence service too. Verify during implementation by checking whether the integration test `testDuplicateReturns422WhenValidationFails` still passes.

**Step 4: Add `resultToResponse()` helper, remove old methods**

```php
/**
 * Convert a failed Result into a 422 JSON error response.
 *
 * @param Result<mixed> $result
 */
private function resultToResponse(Result $result): HTTPResponse
{
    $messages = array_map(
        static fn (ValidationError $error): string => $error->message,
        $result->errors(),
    );

    $this->jsonError(422, implode(' ', $messages));
}
```

Delete `reorderElements()` (lines 382-391) and `extractValidationMessages()` (lines 399-408).

**Step 5: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 6: Run all tests**

Run: `make test`
Expected: ALL PASS — integration tests `testCreateReturns422WhenValidationFails` and `testDuplicateReturns422WhenValidationFails` must produce identical HTTP responses.

**Step 7: Commit**

```bash
git add src/Controllers/ElementalGridController.php
git commit -m "refactor: controller uses Result pattern instead of try/catch ValidationException"
```

---

### Task 6: Delete unused domain exceptions

**Files:**
- Delete: `src/Exception/HierarchyValidationException.php`
- Delete: `src/Exception/ElementNotFoundException.php`
- Delete: `src/Exception/PermissionDeniedException.php`
- Delete: `tests/Unit/Exception/HierarchyValidationExceptionTest.php`
- Delete: `tests/Unit/Exception/ElementNotFoundExceptionTest.php`
- Delete: `tests/Unit/Exception/PermissionDeniedExceptionTest.php`

**Step 1: Verify no production code references these classes**

Search for imports/usage of each class across `src/`. They should only appear in their own files and tests.

**Step 2: Delete the files**

```bash
rm src/Exception/HierarchyValidationException.php
rm src/Exception/ElementNotFoundException.php
rm src/Exception/PermissionDeniedException.php
rm tests/Unit/Exception/HierarchyValidationExceptionTest.php
rm tests/Unit/Exception/ElementNotFoundExceptionTest.php
rm tests/Unit/Exception/PermissionDeniedExceptionTest.php
```

**Step 3: Run all tests**

Run: `make test`
Expected: ALL PASS (6 fewer tests total)

**Step 4: Run PHPStan**

Run: `make analyse`
Expected: PASS

**Step 5: Commit**

```bash
git add -A
git commit -m "chore: remove unused domain exceptions replaced by Result pattern"
```

---

### Task 7: Final verification

**Step 1: Run full QA suite**

Run: `make qa`
Expected: PHPStan PASS, all PHP tests PASS, JS QA PASS

**Step 2: Run integration tests specifically**

Run: `make test-integration`
Expected: ALL PASS — especially `testCreateReturns422WhenValidationFails` and `testDuplicateReturns422WhenValidationFails`

**Step 3: Verify no leftover references**

Search for `ValidationException` in `src/` — should only appear in `ElementPersistenceService.php`.
Search for deleted class names — should return zero results.

**Step 4: Commit any remaining changes and push**

```bash
git status
git push
```
