<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Validation;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Elements\ElementSection;
use WeDevelop\ElementalGrid\Validation\ReorderValidator;

/**
 * Unit tests for ReorderValidator — covers same-area short-circuit,
 * orphaned area handling, and circular reference detection.
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

    // -- Circular reference detection --

    public function testCircularReferenceDetectedAtDepthOne(): void
    {
        // Section(42) is being moved into its own child area
        // Area(20).getOwnerPage() = Section(42) → circular!
        $area = $this->createAreaMock(20);
        $section = $this->createContainerElementMock(42, 10);

        $area->method('getOwnerPage')->willReturn($section);
        $section->method('singular_name')->willReturn('Section');

        $result = $this->validator->validate($section, $area);

        $this->assertTrue($result->isErr());
        $this->assertStringContainsString('circular', strtolower($result->errors()[0]->message));
        $this->assertSame('placement', $result->errors()[0]->field);
    }

    public function testCircularReferenceDetectedAtDepthTwo(): void
    {
        // Section(42) → Area(20) → Row(99) → Area(30)
        // Moving Section(42) to Area(30) would create a cycle
        $targetArea = $this->createAreaMock(30);
        $section = $this->createContainerElementMock(42, 10);

        // Area(30).getOwnerPage() = Row(99) — not Section(42), keep walking
        $row = $this->createContainerElementMock(99, 20);

        // Row(99).Parent() = Area(20) — Parent() is a magic method via __call
        $parentArea = $this->createAreaMock(20);
        $row->method('__call')->willReturnCallback(
            static fn (string $method): mixed => $method === 'Parent' ? $parentArea : null,
        );

        // Area(20).getOwnerPage() = Section(42) → match! Circular reference.
        $parentArea->method('getOwnerPage')->willReturn($section);
        $targetArea->method('getOwnerPage')->willReturn($row);

        $section->method('singular_name')->willReturn('Section');

        $result = $this->validator->validate($section, $targetArea);

        $this->assertTrue($result->isErr());
        $this->assertStringContainsString('circular', strtolower($result->errors()[0]->message));
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

    /**
     * Create a mock that extends BaseElement and implements ElementContainerInterface.
     * ElementSection satisfies both — used for circular reference detection tests.
     */
    private function createContainerElementMock(int $id, int $parentId): ElementSection&MockObject
    {
        $element = $this->createMock(ElementSection::class);

        $fields = ['ID' => $id, 'ParentID' => $parentId];

        $element->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        return $element;
    }
}
