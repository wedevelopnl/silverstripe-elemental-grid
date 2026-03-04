<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Exception\GridDomainException;

#[CoversClass(GridDomainException::class)]
final class GridDomainExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $exception = $this->createConcreteException(
            userMessage: 'User message',
            detailedMessage: 'Detailed message',
            statusCode: 500,
        );

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testGetUserMessageReturnsSafeMessage(): void
    {
        $exception = $this->createConcreteException(
            userMessage: 'Something went wrong.',
            detailedMessage: 'Internal error at line 42',
            statusCode: 500,
        );

        $this->assertSame('Something went wrong.', $exception->getUserMessage());
    }

    public function testGetMessageReturnsDetailedMessage(): void
    {
        $exception = $this->createConcreteException(
            userMessage: 'Something went wrong.',
            detailedMessage: 'Internal error at line 42',
            statusCode: 500,
        );

        $this->assertSame('Internal error at line 42', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    }

    public function testGetStatusCodeReturnsProvidedCode(): void
    {
        $exception = $this->createConcreteException(
            userMessage: 'Oops',
            detailedMessage: 'Details',
            statusCode: 422,
        );

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testPreviousThrowablePropagates(): void
    {
        $cause = new \InvalidArgumentException('root cause');
        $exception = $this->createConcreteException(
            userMessage: 'Oops',
            detailedMessage: 'Details',
            statusCode: 500,
            previous: $cause,
        );

        $this->assertSame($cause, $exception->getPrevious());
    }

    public function testPreviousDefaultsToNull(): void
    {
        $exception = $this->createConcreteException(
            userMessage: 'Oops',
            detailedMessage: 'Details',
            statusCode: 500,
        );

        $this->assertNull($exception->getPrevious());
    }

    private function createConcreteException(
        string $userMessage,
        string $detailedMessage,
        int $statusCode,
        ?\Throwable $previous = null,
    ): GridDomainException {
        return new class ($userMessage, $detailedMessage, $statusCode, $previous) extends GridDomainException {};
    }
}
