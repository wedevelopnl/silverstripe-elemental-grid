<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Exception\GridDomainException;
use WeDevelop\ElementalGrid\Exception\HierarchyValidationException;

#[CoversClass(HierarchyValidationException::class)]
final class HierarchyValidationExceptionTest extends TestCase
{
    public function testExtendsGridDomainException(): void
    {
        $exception = HierarchyValidationException::forInvalidPlacement('Section', 'Row');

        $this->assertInstanceOf(GridDomainException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testForInvalidPlacementStatusCode(): void
    {
        $exception = HierarchyValidationException::forInvalidPlacement('Section', 'Row');

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testForInvalidPlacementUserMessageContainsNoTypeNames(): void
    {
        $exception = HierarchyValidationException::forInvalidPlacement('Section', 'Row');

        $this->assertSame('This element cannot be placed here.', $exception->getUserMessage());
        $this->assertStringNotContainsString('Section', $exception->getUserMessage());
        $this->assertStringNotContainsString('Row', $exception->getUserMessage());
    }

    public function testForInvalidPlacementDetailedMessageContainsTypeNames(): void
    {
        $exception = HierarchyValidationException::forInvalidPlacement('Section', 'Row');

        $this->assertSame('Section cannot be placed inside Row.', $exception->getMessage());
    }

    public function testForInvalidPlacementWithDifferentTypes(): void
    {
        $exception = HierarchyValidationException::forInvalidPlacement('Column', 'Section');

        $this->assertSame('Column cannot be placed inside Section.', $exception->getMessage());
    }

    public function testPreviousThrowablePropagates(): void
    {
        $cause = new \LogicException('hierarchy error');
        $exception = new HierarchyValidationException(
            'User msg',
            'Detail msg',
            422,
            $cause,
        );

        $this->assertSame($cause, $exception->getPrevious());
    }
}
