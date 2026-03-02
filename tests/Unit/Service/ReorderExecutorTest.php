<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use WeDevelop\ElementalGrid\Repository\ElementRepositoryInterface;
use WeDevelop\ElementalGrid\Service\ReorderExecutor;

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
        // [A(1), B(2), C(3), D(4), E(5)] → move D to position 1
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

        // D, B, C should be written (Sort changes); A and E unchanged
        $d->expects($this->once())->method('write');
        $b->expects($this->once())->method('write');
        $c->expects($this->once())->method('write');
        $a->expects($this->never())->method('write');
        $e->expects($this->never())->method('write');

        $result = $this->executor->execute($d, $area, 1);

        $this->assertTrue($result->isOk());
        $this->assertSame($d, $result->unwrap());
        $this->assertSame(2, $d->Sort);
        $this->assertSame(3, $b->Sort);
        $this->assertSame(4, $c->Sort);
    }

    public function testSameAreaMoveForward(): void
    {
        // [A(1), B(2), C(3), D(4), E(5)] → move B to position 3
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

        $b->expects($this->once())->method('write');
        $c->expects($this->once())->method('write');
        $d->expects($this->once())->method('write');
        $a->expects($this->never())->method('write');
        $e->expects($this->never())->method('write');

        $result = $this->executor->execute($b, $area, 3);

        $this->assertTrue($result->isOk());
        $this->assertSame(2, $c->Sort);
        $this->assertSame(3, $d->Sort);
        $this->assertSame(4, $b->Sort);
    }

    public function testSamePositionIsNoOp(): void
    {
        // [A(1), B(2), C(3)] → move B to position 1 (already there)
        $area = $this->createAreaMock(10);
        [$a, $b, $c] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c]);

        $a->expects($this->never())->method('write');
        $b->expects($this->never())->method('write');
        $c->expects($this->never())->method('write');

        $result = $this->executor->execute($b, $area, 1);

        $this->assertTrue($result->isOk());
    }

    public function testCrossAreaMoveUpdatesParentId(): void
    {
        // Source area 10: [A(1), B(2)] → move B to target area 20 at position 0
        // Target area 20: [X(1), Y(2)] → [B(1), X(2), Y(3)]
        $targetArea = $this->createAreaMock(20);

        $b = $this->createElementMock(2, 2, 10);
        $x = $this->createElementMock(3, 1, 20);
        $y = $this->createElementMock(4, 2, 20);

        $this->repository->method('findByAreaIds')->with([20])->willReturn([$x, $y]);

        $b->expects($this->once())->method('write');
        $x->expects($this->once())->method('write');
        $y->expects($this->once())->method('write');

        $result = $this->executor->execute($b, $targetArea, 0);

        $this->assertTrue($result->isOk());
        $this->assertSame(20, $b->ParentID);
        $this->assertSame(1, $b->Sort);
        $this->assertSame(2, $x->Sort);
        $this->assertSame(3, $y->Sort);
    }

    public function testPositionClampedToEnd(): void
    {
        // [A(1), B(2)] → move A to position 999 → clamped to end (position 1)
        $area = $this->createAreaMock(10);
        [$a, $b] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b]);

        $a->expects($this->once())->method('write');
        $b->expects($this->once())->method('write');

        $result = $this->executor->execute($a, $area, 999);

        $this->assertTrue($result->isOk());
        $this->assertSame(1, $b->Sort);
        $this->assertSame(2, $a->Sort);
    }

    public function testEmptyTargetAreaCrossArea(): void
    {
        // Move element to empty target area → becomes sole member with Sort=1
        $targetArea = $this->createAreaMock(20);
        $element = $this->createElementMock(1, 1, 10);

        $this->repository->method('findByAreaIds')->with([20])->willReturn([]);

        $element->expects($this->once())->method('write');

        $result = $this->executor->execute($element, $targetArea, 0);

        $this->assertTrue($result->isOk());
        $this->assertSame(20, $element->ParentID);
        $this->assertSame(1, $element->Sort);
    }

    public function testMoveToPositionZero(): void
    {
        // [A(1), B(2), C(3)] → move C to position 0
        // Expected: [C(1), A(2), B(3)]
        $area = $this->createAreaMock(10);
        [$a, $b, $c] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
            ['id' => 3, 'sort' => 3, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b, $c]);

        $c->expects($this->once())->method('write');
        $a->expects($this->once())->method('write');
        $b->expects($this->once())->method('write');

        $result = $this->executor->execute($c, $area, 0);

        $this->assertTrue($result->isOk());
        $this->assertSame(1, $c->Sort);
        $this->assertSame(2, $a->Sort);
        $this->assertSame(3, $b->Sort);
    }

    public function testValidationExceptionFromWriteReturnsFail(): void
    {
        $area = $this->createAreaMock(10);
        [$a, $b] = $this->createElementMocks([
            ['id' => 1, 'sort' => 1, 'parentId' => 10],
            ['id' => 2, 'sort' => 2, 'parentId' => 10],
        ]);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a, $b]);

        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult->method('getMessages')->willReturn([
            ['message' => 'Write failed.', 'fieldName' => ''],
        ]);

        $exception = $this->createMock(ValidationException::class);
        $exception->method('getResult')->willReturn($validationResult);

        $a->method('write')->willThrowException($exception);

        $result = $this->executor->execute($b, $area, 0);

        $this->assertTrue($result->isErr());
        $this->assertSame('Write failed.', $result->errors()[0]->message);
    }

    public function testCrossAreaMoveToMiddle(): void
    {
        // Source area 10: [A(1), B(2)] → move B to target area 20 at position 1
        // Target area 20: [X(1), Y(2)] → [X(1), B(2), Y(3)]
        $targetArea = $this->createAreaMock(20);

        $b = $this->createElementMock(2, 2, 10);
        $x = $this->createElementMock(3, 1, 20);
        $y = $this->createElementMock(4, 2, 20);

        $this->repository->method('findByAreaIds')->with([20])->willReturn([$x, $y]);

        $b->expects($this->once())->method('write');
        $y->expects($this->once())->method('write');
        $x->expects($this->never())->method('write');

        $result = $this->executor->execute($b, $targetArea, 1);

        $this->assertTrue($result->isOk());
        $this->assertSame(20, $b->ParentID);
        $this->assertSame(1, $x->Sort);
        $this->assertSame(2, $b->Sort);
        $this->assertSame(3, $y->Sort);
    }

    public function testSameAreaSingleElementNoOp(): void
    {
        // [A(1)] → move A to position 0 (only element, already there)
        $area = $this->createAreaMock(10);
        $a = $this->createElementMock(1, 1, 10);

        $this->repository->method('findByAreaIds')->with([10])->willReturn([$a]);

        $a->expects($this->never())->method('write');

        $result = $this->executor->execute($a, $area, 0);

        $this->assertTrue($result->isOk());
    }

    public function testSameAreaMoveDoesNotTouchChildAreaElements(): void
    {
        // Container element (Section) with children in ChildArea(50).
        // Reorder Section within its parent area(10) — children must be unaffected.
        // [A(1), Section(2)] → move Section to position 0 → [Section(1), A(2)]
        $area = $this->createAreaMock(10);
        $a = $this->createElementMock(1, 1, 10);
        $section = $this->createElementMock(2, 2, 10);

        // Repository must only be queried for the target area (10), never for
        // the container's child area (50) or any other area.
        $this->repository->expects($this->once())
            ->method('findByAreaIds')
            ->with([10])
            ->willReturn([$a, $section]);

        $result = $this->executor->execute($section, $area, 0);

        $this->assertTrue($result->isOk());
        $this->assertSame(1, $section->Sort);
        $this->assertSame(2, $a->Sort);
    }

    public function testCrossAreaMoveDoesNotTouchChildAreaElements(): void
    {
        // Container element (Section) with children in ChildArea(50).
        // Move Section from area(10) to area(20) — children must be unaffected.
        // Target area 20: [X(1)] → [Section(1), X(2)]
        $targetArea = $this->createAreaMock(20);
        $section = $this->createElementMock(2, 1, 10);
        $x = $this->createElementMock(3, 1, 20);

        // Repository must only be queried for the target area (20), never for
        // the source area (10) or the container's child area (50).
        $this->repository->expects($this->once())
            ->method('findByAreaIds')
            ->with([20])
            ->willReturn([$x]);

        $result = $this->executor->execute($section, $targetArea, 0);

        $this->assertTrue($result->isOk());
        $this->assertSame(20, $section->ParentID);
        $this->assertSame(1, $section->Sort);
        $this->assertSame(2, $x->Sort);
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
