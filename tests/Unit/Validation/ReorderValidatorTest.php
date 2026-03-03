<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Validation;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Validation\ReorderValidator;

/**
 * Unit tests for ReorderValidator — covers same-area short-circuit
 * and orphaned area handling.
 *
 * Hierarchy rule tests (allowed_elements, disallowed_elements, can_be_root)
 * are covered by integration tests since they require SilverStripe's static
 * Config system which cannot be mocked in unit tests.
 */
#[CoversClass(ReorderValidator::class)]
final class ReorderValidatorTest extends TestCase
{
    private ReorderValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ReorderValidator();
    }

    // -- Same-area moves --

    public function testSameAreaMoveIsAlwaysValid(): void
    {
        $area = $this->createAreaMock(10);
        $element = $this->createBaseElementMock(1, 10);

        // Same area: no hierarchy or circular ref checks needed
        $area->expects($this->never())->method('getOwnerPage');

        $result = $this->validator->validate($element, $area);

        $this->assertTrue($result->isOk());
    }

    // -- Orphaned area --

    public function testOrphanedAreaAllowsAnyElement(): void
    {
        $area = $this->createAreaMock(20);
        $element = $this->createBaseElementMock(1, 10);

        $area->method('getOwnerPage')->willReturn(null);

        $result = $this->validator->validate($element, $area);

        $this->assertTrue($result->isOk());
    }

    // -- Mock helpers --

    private function createAreaMock(int $id): ElementalArea&MockObject
    {
        $area = $this->createMock(ElementalArea::class);

        $fields = ['ID' => $id];

        $area->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        $area->method('exists')->willReturn(true);

        return $area;
    }

    private function createBaseElementMock(int $id, int $parentId): BaseElement&MockObject
    {
        $element = $this->createMock(BaseElement::class);

        $fields = ['ID' => $id, 'ParentID' => $parentId];

        $element->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        return $element;
    }

}
