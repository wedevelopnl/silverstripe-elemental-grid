<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Contract\ReorderExecutorInterface;
use WeDevelop\Grid\Contract\ReorderValidatorInterface;
use WeDevelop\Grid\Model\Result;
use WeDevelop\Grid\Model\ValidationError;
use WeDevelop\Grid\Service\ElementPersistenceService;
use WeDevelop\Grid\Service\ReorderService;

#[CoversClass(ReorderService::class)]
final class ReorderServiceTest extends TestCase
{
    private ReorderValidatorInterface&MockObject $validator;

    private ReorderExecutorInterface&MockObject $executor;

    private ElementPersistenceService&MockObject $persistenceService;

    private ReorderService $service;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ReorderValidatorInterface::class);
        $this->executor = $this->createMock(ReorderExecutorInterface::class);
        $this->persistenceService = $this->createMock(ElementPersistenceService::class);
        $this->service = new ReorderService($this->validator, $this->executor, $this->persistenceService);
    }

    public function testHappyPathCallsValidatorThenExecutorThenPersist(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);
        $dirtyElements = [$element];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($element, $area)
            ->willReturn(Result::ok($element));

        $this->executor->expects($this->once())
            ->method('execute')
            ->with($element, $area, 42)
            ->willReturn(Result::ok($dirtyElements));

        $this->persistenceService->expects($this->once())
            ->method('persistBatch')
            ->with($dirtyElements)
            ->willReturn(Result::ok(null));

        $result = $this->service->reorder($element, $area, 42);

        self::assertTrue($result->isOk());
        self::assertSame($element, $result->unwrap());
    }

    public function testValidationFailureShortCircuitsExecution(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')
            ->willReturn(Result::fail(new ValidationError(message: 'Not allowed.')));

        $this->executor->expects($this->never())->method('execute');
        $this->persistenceService->expects($this->never())->method('persistBatch');

        $result = $this->service->reorder($element, $area, null);

        self::assertTrue($result->isErr());
        self::assertSame('Not allowed.', $result->errors()[0]->message);
    }

    public function testValidationErrorsPropagatedToResult(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')
            ->willReturn(Result::fail(
                new ValidationError(message: 'First error.', field: 'placement'),
                new ValidationError(message: 'Second error.'),
            ));

        $result = $this->service->reorder($element, $area, null);

        self::assertTrue($result->isErr());
        self::assertCount(2, $result->errors());
        self::assertSame('First error.', $result->errors()[0]->message);
        self::assertSame('placement', $result->errors()[0]->field);
        self::assertSame('Second error.', $result->errors()[1]->message);
    }

    public function testPersistenceFailurePropagated(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')->willReturn(Result::ok($element));
        $this->executor->method('execute')->willReturn(Result::ok([$element]));

        $this->persistenceService->method('persistBatch')
            ->willReturn(Result::fail(new ValidationError(message: 'Write failed.')));

        $result = $this->service->reorder($element, $area, null);

        self::assertTrue($result->isErr());
        self::assertSame('Write failed.', $result->errors()[0]->message);
    }

    public function testEmptyDirtyListStillCallsPersistBatch(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')->willReturn(Result::ok($element));
        $this->executor->method('execute')->willReturn(Result::ok([]));

        $this->persistenceService->expects($this->once())
            ->method('persistBatch')
            ->with([])
            ->willReturn(Result::ok(null));

        $result = $this->service->reorder($element, $area, 5);

        self::assertTrue($result->isOk());
        self::assertSame($element, $result->unwrap());
    }

    public function testExecutorFailurePropagated(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')->willReturn(Result::ok($element));

        $this->executor->method('execute')
            ->willReturn(Result::fail(new ValidationError(
                message: 'The reference element no longer exists in the target area.',
                field: 'afterElementID',
            )));

        $this->persistenceService->expects($this->never())->method('persistBatch');

        $result = $this->service->reorder($element, $area, 999);

        self::assertTrue($result->isErr());
        self::assertSame('afterElementID', $result->errors()[0]->field);
    }
}
