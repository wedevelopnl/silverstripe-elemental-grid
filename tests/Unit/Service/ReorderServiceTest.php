<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Service;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Contract\ReorderExecutorInterface;
use WeDevelop\ElementalGrid\Contract\ReorderValidatorInterface;
use WeDevelop\ElementalGrid\Model\Result;
use WeDevelop\ElementalGrid\Model\ValidationError;
use WeDevelop\ElementalGrid\Service\ReorderService;

#[CoversClass(ReorderService::class)]
final class ReorderServiceTest extends TestCase
{
    private ReorderValidatorInterface&MockObject $validator;

    private ReorderExecutorInterface&MockObject $executor;

    private ReorderService $service;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ReorderValidatorInterface::class);
        $this->executor = $this->createMock(ReorderExecutorInterface::class);
        $this->service = new ReorderService($this->validator, $this->executor);
    }

    public function testHappyPathCallsValidatorThenExecutor(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($element, $area)
            ->willReturn(Result::ok($element));

        $this->executor->expects($this->once())
            ->method('execute')
            ->with($element, $area, 2)
            ->willReturn(Result::ok($element));

        $result = $this->service->reorder($element, $area, 2);

        $this->assertTrue($result->isOk());
        $this->assertSame($element, $result->unwrap());
    }

    public function testValidationFailureShortCircuitsExecution(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')
            ->willReturn(Result::fail(new ValidationError(message: 'Not allowed.')));

        $this->executor->expects($this->never())->method('execute');

        $result = $this->service->reorder($element, $area, 0);

        $this->assertTrue($result->isErr());
        $this->assertSame('Not allowed.', $result->errors()[0]->message);
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

        $result = $this->service->reorder($element, $area, 0);

        $this->assertTrue($result->isErr());
        $this->assertCount(2, $result->errors());
        $this->assertSame('First error.', $result->errors()[0]->message);
        $this->assertSame('placement', $result->errors()[0]->field);
        $this->assertSame('Second error.', $result->errors()[1]->message);
    }

    public function testExecutorResultReturnedAsIs(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')->willReturn(Result::ok($element));

        $executorResult = Result::ok($element);
        $this->executor->method('execute')->willReturn($executorResult);

        $result = $this->service->reorder($element, $area, 5);

        $this->assertSame($executorResult, $result);
    }

    public function testExecutorFailurePropagated(): void
    {
        $element = $this->createMock(BaseElement::class);
        $area = $this->createMock(ElementalArea::class);

        $this->validator->method('validate')->willReturn(Result::ok($element));

        $this->executor->method('execute')
            ->willReturn(Result::fail(new ValidationError(message: 'Write failed.')));

        $result = $this->service->reorder($element, $area, 0);

        $this->assertTrue($result->isErr());
        $this->assertSame('Write failed.', $result->errors()[0]->message);
    }
}
