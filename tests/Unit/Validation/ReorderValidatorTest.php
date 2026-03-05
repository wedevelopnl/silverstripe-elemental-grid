<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SilverStripe\ORM\DataObject;
use WeDevelop\Grid\Model\GridElement;
use WeDevelop\Grid\Validation\ReorderValidator;

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
        $area = $this->createParentMock(10);
        $element = $this->createElementMock(1, 10);

        // Same area: no hierarchy or circular ref checks needed
        $area->expects($this->never())->method('getOwnerPage');

        $result = $this->validator->validate($element, $area);

        $this->assertTrue($result->isOk());
    }

    // -- Orphaned area --

    public function testOrphanedAreaAllowsAnyElement(): void
    {
        $area = $this->createParentMock(20);
        $element = $this->createElementMock(1, 10);

        $area->method('getOwnerPage')->willReturn(null);

        $result = $this->validator->validate($element, $area);

        $this->assertTrue($result->isOk());
    }

    // -- Mock helpers --

    private function createParentMock(int $id): DataObject&MockObject
    {
        $area = $this->createMock(DataObject::class);

        $fields = ['ID' => $id];

        $area->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        $area->method('exists')->willReturn(true);

        return $area;
    }

    private function createElementMock(int $id, int $parentId): GridElement&MockObject
    {
        $element = $this->createMock(GridElement::class);

        $fields = ['ID' => $id, 'ParentID' => $parentId];

        $element->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        return $element;
    }

}
