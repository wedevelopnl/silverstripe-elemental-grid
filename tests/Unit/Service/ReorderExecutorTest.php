<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Repository\ElementRepositoryInterface;
use WeDevelop\Grid\Service\ReorderExecutor;

#[CoversClass(ReorderExecutor::class)]
final class ReorderExecutorTest extends TestCase
{
    private ElementRepositoryInterface&MockObject $repository;

    private ReorderExecutor $executor;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(ElementRepositoryInterface::class);
        $this->executor = new ReorderExecutor($this->repository);
    }

    public function testSameAreaMoveBackward(): void
    {
        // [A(1), B(2), C(3), D(4), E(5)] → move D after A
        // Expected: [A(1), D(2), B(3), C(4), E(5)]
        $area = $this->createAreaMock(10);
        [$a, $b, $c, $d, $e] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
            ['id' => 4, 'sort' => 4, 'parentId' => 10],
            ['id' => 5, 'sort' => 5, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c, $d, $e]);

        $result = $this->executor->execute($d, $area, $a->ID);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $a->Sort);
        self::assertSame(2, $d->Sort);
        self::assertSame(3, $b->Sort);
        self::assertSame(4, $c->Sort);
        self::assertSame(5, $e->Sort);
        self::assertSame([$d, $b, $c], $dirty);
    }

    public function testSameAreaMoveForward(): void
    {
        // [A(1), B(2), C(3), D(4), E(5)] → move B after D
        // Expected: [A(1), C(2), D(3), B(4), E(5)]
        $area = $this->createAreaMock(10);
        [$a, $b, $c, $d, $e] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
            ['id' => 4, 'sort' => 4, 'parentId' => 10],
            ['id' => 5, 'sort' => 5, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c, $d, $e]);

        $result = $this->executor->execute($b, $area, $d->ID);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $a->Sort);
        self::assertSame(2, $c->Sort);
        self::assertSame(3, $d->Sort);
        self::assertSame(4, $b->Sort);
        self::assertSame(5, $e->Sort);
        self::assertSame([$c, $d, $b], $dirty);
    }

    public function testSamePositionIsNoOp(): void
    {
        // [A(1), B(2), C(3)] → move B after A (already there)
        $area = $this->createAreaMock(10);
        [$a, $b, $c] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c]);

        $result = $this->executor->execute($b, $area, $a->ID);

        self::assertTrue($result->isOk());
        self::assertSame([], $result->unwrap());
    }

    public function testCrossAreaMoveUpdatesParentId(): void
    {
        // Source area 10: [A(1), B(2)] → move B to target area 20 at first position
        // Target area 20: [X(1), Y(2)] → [B(1), X(2), Y(3)]
        // Source area 10: [A(1)] → no gaps, A unchanged
        $targetArea = $this->createAreaMock(20);

        $a = $this->createElementMock(1, 1, 10);
        $b = $this->createElementMock(2, 2, 10);
        $x = $this->createElementMock(3, 1, 20);
        $y = $this->createElementMock(4, 2, 20);

        $this->repository->method('findByAreaIds')->willReturnCallback(
            static fn (array $ids): array => match ($ids) {
                [20] => [$x, $y],
                [10] => [$a, $b],
                default => [],
            },
        );

        $result = $this->executor->execute($b, $targetArea, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(20, $b->ParentID);
        self::assertSame(1, $b->Sort);
        self::assertSame(2, $x->Sort);
        self::assertSame(3, $y->Sort);
        // Source: A stays at Sort=1, not dirty
        self::assertSame(1, $a->Sort);
        self::assertSame([$b, $x, $y], $dirty);
    }

    public function testSameAreaMoveToEnd(): void
    {
        // [A(1), B(2)] → move A after B (last element)
        $area = $this->createAreaMock(10);
        [$a, $b] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b]);

        $result = $this->executor->execute($a, $area, $b->ID);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $b->Sort);
        self::assertSame(2, $a->Sort);
        self::assertSame([$b, $a], $dirty);
    }

    public function testEmptyTargetAreaCrossArea(): void
    {
        // Move element to empty target area → becomes sole member with Sort=1
        // Source area 10: [Element(1)] → empty after move
        $targetArea = $this->createAreaMock(20);
        $element = $this->createElementMock(1, 1, 10);

        $this->repository->method('findByAreaIds')->willReturnCallback(
            static fn (array $ids): array => match ($ids) {
                [20] => [],
                [10] => [$element],
                default => [],
            },
        );

        $result = $this->executor->execute($element, $targetArea, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(20, $element->ParentID);
        self::assertSame(1, $element->Sort);
        self::assertSame([$element], $dirty);
    }

    public function testMoveToFirstPosition(): void
    {
        // [A(1), B(2), C(3)] → move C to first (afterElementId=null)
        // Expected: [C(1), A(2), B(3)]
        $area = $this->createAreaMock(10);
        [$a, $b, $c] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c]);

        $result = $this->executor->execute($c, $area, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $c->Sort);
        self::assertSame(2, $a->Sort);
        self::assertSame(3, $b->Sort);
        self::assertSame([$c, $a, $b], $dirty);
    }

    public function testCrossAreaMoveToMiddle(): void
    {
        // Source area 10: [A(1), B(2)] → move B to target area 20 after X
        // Target area 20: [X(1), Y(2)] → [X(1), B(2), Y(3)]
        // Source area 10: [A(1)] → no gaps
        $targetArea = $this->createAreaMock(20);

        $a = $this->createElementMock(1, 1, 10);
        $b = $this->createElementMock(2, 2, 10);
        $x = $this->createElementMock(3, 1, 20);
        $y = $this->createElementMock(4, 2, 20);

        $this->repository->method('findByAreaIds')->willReturnCallback(
            static fn (array $ids): array => match ($ids) {
                [20] => [$x, $y],
                [10] => [$a, $b],
                default => [],
            },
        );

        $result = $this->executor->execute($b, $targetArea, $x->ID);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(20, $b->ParentID);
        self::assertSame(1, $x->Sort);
        self::assertSame(2, $b->Sort);
        self::assertSame(3, $y->Sort);
        // X unchanged (Sort stays 1), only B and Y dirty in target; A unchanged in source
        self::assertSame([$b, $y], $dirty);
    }

    public function testSameAreaSingleElementNoOp(): void
    {
        // [A(1)] → move A to first (only element, already there)
        $area = $this->createAreaMock(10);
        $a = $this->createElementMock(1, 1, 10);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a]);

        $result = $this->executor->execute($a, $area, null);

        self::assertTrue($result->isOk());
        self::assertSame([], $result->unwrap());
    }

    public function testSameAreaMoveDoesNotTouchChildAreaElements(): void
    {
        // Container element (Section) with children in ChildArea(50).
        // Reorder Section within its parent area(10) — children must be unaffected.
        // [A(1), Section(2)] → move Section to first → [Section(1), A(2)]
        $area = $this->createAreaMock(10);
        $a = $this->createElementMock(1, 1, 10);
        $section = $this->createElementMock(2, 2, 10);

        // Repository must only be queried for the target area (10), never for
        // the container's child area (50) or any other area.
        $this->repository->expects($this->once())
            ->method('findByAreaIds')
            ->with([10])
            ->willReturn([$a, $section]);

        $result = $this->executor->execute($section, $area, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $section->Sort);
        self::assertSame(2, $a->Sort);
        self::assertSame([$section, $a], $dirty);
    }

    public function testCrossAreaMoveDoesNotTouchChildAreaElements(): void
    {
        // Container element (Section) with children in ChildArea(50).
        // Move Section from area(10) to area(20) — children must be unaffected.
        // Target area 20: [X(1)] → [Section(1), X(2)]
        // Source area 10: [] → empty after move (was only element)
        $targetArea = $this->createAreaMock(20);
        $section = $this->createElementMock(2, 1, 10);
        $x = $this->createElementMock(3, 1, 20);

        // Repository queried twice: target area (20) then source area (10).
        // Never for child area (50).
        $this->repository->expects($this->exactly(2))
            ->method('findByAreaIds')
            ->willReturnCallback(
                static fn (array $ids): array => match ($ids) {
                    [20] => [$x],
                    [10] => [$section],
                    default => [],
                },
            );

        $result = $this->executor->execute($section, $targetArea, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(20, $section->ParentID);
        self::assertSame(1, $section->Sort);
        self::assertSame(2, $x->Sort);
        self::assertSame([$section, $x], $dirty);
    }

    public function testAfterElementNotFoundReturnsError(): void
    {
        // Pass a non-existent afterElementId → Result::fail
        $area = $this->createAreaMock(10);
        [$a, $b] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b]);

        $result = $this->executor->execute($a, $area, 999);

        self::assertTrue($result->isErr());
        self::assertSame('afterElementID', $result->errors()[0]->field);
    }

    public function testCrossAreaMoveClosesSortGapsInSourceArea(): void
    {
        // Source area 10: [A(1), B(2), C(3)] → move B to target area 20
        // Target area 20: [] → [B(1)]
        // Source area 10: [A(1), C(3)] → C re-sorts to 2, appears in dirty list
        $targetArea = $this->createAreaMock(20);

        $a = $this->createElementMock(1, 1, 10);
        $b = $this->createElementMock(2, 2, 10);
        $c = $this->createElementMock(3, 3, 10);

        $this->repository->method('findByAreaIds')->willReturnCallback(
            static fn (array $ids): array => match ($ids) {
                [20] => [],
                [10] => [$a, $b, $c],
                default => [],
            },
        );

        $result = $this->executor->execute($b, $targetArea, null);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        // Target: B is sole member at Sort=1
        self::assertSame(20, $b->ParentID);
        self::assertSame(1, $b->Sort);
        // Source: A stays at 1, C re-sorts from 3 to 2
        self::assertSame(1, $a->Sort);
        self::assertSame(2, $c->Sort);
        // Dirty: B (target, ParentID changed) + C (source, Sort changed)
        self::assertSame([$b, $c], $dirty);
    }

    public function testSameAreaMoveAfterSpecificElement(): void
    {
        // [A(1), B(2), C(3), D(4), E(5)] → move B after C
        // Expected: [A(1), C(2), B(3), D(4), E(5)]
        $area = $this->createAreaMock(10);
        [$a, $b, $c, $d, $e] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
            ['id' => 4, 'sort' => 4, 'parentId' => 10],
            ['id' => 5, 'sort' => 5, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c, $d, $e]);

        $result = $this->executor->execute($b, $area, $c->ID);

        self::assertTrue($result->isOk());
        $dirty = $result->unwrap();

        self::assertSame(1, $a->Sort);
        self::assertSame(2, $c->Sort);
        self::assertSame(3, $b->Sort);
        self::assertSame(4, $d->Sort);
        self::assertSame(5, $e->Sort);
        // C and B swapped: C 3→2, B 2→3
        self::assertSame([$c, $b], $dirty);
    }

    /**
     * @param list<array{id: int, sort: int, parentId: int}> $specs
     * @return list<BaseElement&MockObject>
     */
    private function createElementMocks(array $specs): array
    {
        return array_map(
            fn (array $spec): BaseElement&MockObject => $this->createElementMock($spec['id'], $spec['sort'], $spec['parentId']),
            $specs,
        );
    }

    private function createElementMock(int $id, int $sort, int $parentId): BaseElement&MockObject
    {
        $element = $this->createMock(BaseElement::class);

        $fields = ['ID' => $id, 'Sort' => $sort, 'ParentID' => $parentId];

        $element->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        $element->method('__set')->willReturnCallback(
            static function (string $prop, mixed $value) use (&$fields): void {
                $fields[$prop] = $value;
            },
        );

        return $element;
    }

    private function createAreaMock(int $id): ElementalArea&MockObject
    {
        $area = $this->createMock(ElementalArea::class);

        $fields = ['ID' => $id];

        $area->method('__get')->willReturnCallback(
            static function (string $prop) use (&$fields): mixed {
                return $fields[$prop] ?? null;
            },
        );

        return $area;
    }
}
