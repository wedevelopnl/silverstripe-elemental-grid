<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Exception\GridDomainException;
use WeDevelop\Grid\Exception\InvalidGridValueException;

#[CoversClass(InvalidGridValueException::class)]
final class InvalidGridValueExceptionTest extends TestCase
{
    public function testExtendsGridDomainException(): void
    {
        $exception = InvalidGridValueException::forWidth(15, 12);

        $this->assertInstanceOf(GridDomainException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testForWidthStatusCode(): void
    {
        $exception = InvalidGridValueException::forWidth(15, 12);

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testForWidthUserMessageContainsNoValues(): void
    {
        $exception = InvalidGridValueException::forWidth(15, 12);

        $this->assertSame('The specified column width is invalid.', $exception->getUserMessage());
        $this->assertStringNotContainsString('15', $exception->getUserMessage());
    }

    public function testForWidthDetailedMessageContainsValues(): void
    {
        $exception = InvalidGridValueException::forWidth(15, 12);

        $this->assertSame('Width 15 exceeds maximum of 12 columns.', $exception->getMessage());
    }

    public function testForOffsetStatusCode(): void
    {
        $exception = InvalidGridValueException::forOffset(13, 12);

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testForOffsetUserMessageContainsNoValues(): void
    {
        $exception = InvalidGridValueException::forOffset(13, 12);

        $this->assertSame('The specified column offset is invalid.', $exception->getUserMessage());
        $this->assertStringNotContainsString('13', $exception->getUserMessage());
    }

    public function testForOffsetDetailedMessageContainsValues(): void
    {
        $exception = InvalidGridValueException::forOffset(13, 12);

        $this->assertSame('Offset 13 exceeds maximum of 12 columns.', $exception->getMessage());
    }

    public function testForViewportStatusCode(): void
    {
        $exception = InvalidGridValueException::forViewport('xxl');

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testForViewportUserMessageContainsNoKey(): void
    {
        $exception = InvalidGridValueException::forViewport('xxl');

        $this->assertSame('The specified viewport is not recognised.', $exception->getUserMessage());
        $this->assertStringNotContainsString('xxl', $exception->getUserMessage());
    }

    public function testForViewportDetailedMessageContainsKey(): void
    {
        $exception = InvalidGridValueException::forViewport('xxl');

        $this->assertSame('Viewport key "xxl" is not a valid breakpoint.', $exception->getMessage());
    }

    public function testForColumnCountUserMessageContainsNoValues(): void
    {
        $exception = InvalidGridValueException::forColumnCount(-5);

        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame('The configured column count is invalid.', $exception->getUserMessage());
        $this->assertStringNotContainsString('-5', $exception->getUserMessage());
    }

    public function testForColumnCountDetailedMessageContainsValue(): void
    {
        $exception = InvalidGridValueException::forColumnCount(0);

        $this->assertSame("Column count must be a positive integer, got 0 (int).", $exception->getMessage());
    }

    public function testPreviousThrowablePropagates(): void
    {
        $cause = new \LogicException('validation root');
        $exception = new InvalidGridValueException(
            'User msg',
            'Detail msg',
            422,
            $cause,
        );

        $this->assertSame($cause, $exception->getPrevious());
    }
}
