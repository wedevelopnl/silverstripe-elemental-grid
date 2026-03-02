<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
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
        $this->assertSame($element, $result->unwrap());
    }

    public function testPersistNewReturnsFailOnValidationException(): void
    {
        $exception = $this->createValidationException([
            ['message' => 'Row cannot be placed inside Page.', 'fieldName' => ''],
        ]);

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
        $exception = $this->createValidationException([
            ['message' => 'First error.', 'fieldName' => ''],
            ['message' => 'Second error.', 'fieldName' => ''],
        ]);

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertCount(2, $result->errors());
        $this->assertSame('First error.', $result->errors()[0]->message);
        $this->assertSame('Second error.', $result->errors()[1]->message);
    }

    public function testPersistNewPreservesFieldNameFromValidation(): void
    {
        $exception = $this->createValidationException([
            ['message' => 'Invalid parent.', 'fieldName' => 'ParentID'],
        ]);

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertTrue($result->isErr());
        $this->assertCount(1, $result->errors());
        $this->assertSame('Invalid parent.', $result->errors()[0]->message);
        $this->assertSame('ParentID', $result->errors()[0]->field);
    }

    public function testPersistNewMapsEmptyFieldNameToNull(): void
    {
        $exception = $this->createValidationException([
            ['message' => 'General error.', 'fieldName' => ''],
        ]);

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertNull($result->errors()[0]->field);
    }

    public function testPersistNewFallbackMessageWhenNoValidationMessages(): void
    {
        $exception = $this->createValidationException([]);

        $element = $this->createMock(BaseElement::class);
        $element->method('write')->willThrowException($exception);

        $service = new ElementPersistenceService();
        $result = $service->persistNew($element);

        $this->assertTrue($result->isErr());
        $this->assertCount(1, $result->errors());
        $this->assertSame('Validation failed.', $result->errors()[0]->message);
    }

    /**
     * Build a mocked ValidationException with the given message list.
     *
     * Avoids constructing a real ValidationException which requires the
     * SilverStripe kernel (Injector, i18n) that is unavailable in unit tests.
     *
     * @param list<array{message: string, fieldName: string}> $messages
     */
    private function createValidationException(array $messages): ValidationException
    {
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->method('getMessages')->willReturn($messages);

        $exception = $this->createMock(ValidationException::class);
        $exception->method('getResult')->willReturn($validationResult);

        return $exception;
    }
}
