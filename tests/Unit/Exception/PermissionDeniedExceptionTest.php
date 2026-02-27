<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Exception\GridDomainException;
use WeDevelop\ElementalGrid\Exception\PermissionDeniedException;

#[CoversClass(PermissionDeniedException::class)]
final class PermissionDeniedExceptionTest extends TestCase
{
    public function testExtendsGridDomainException(): void
    {
        $exception = PermissionDeniedException::forAction('edit', 'ElementSection', 1);

        $this->assertInstanceOf(GridDomainException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testForActionStatusCode(): void
    {
        $exception = PermissionDeniedException::forAction('delete', 'ElementRow', 5);

        $this->assertSame(403, $exception->getStatusCode());
    }

    public function testForActionUserMessageContainsNoInternals(): void
    {
        $exception = PermissionDeniedException::forAction('delete', 'ElementRow', 5);

        $this->assertSame('You do not have permission to perform this action.', $exception->getUserMessage());
        $this->assertStringNotContainsString('delete', $exception->getUserMessage());
        $this->assertStringNotContainsString('ElementRow', $exception->getUserMessage());
        $this->assertStringNotContainsString('5', $exception->getUserMessage());
    }

    public function testForActionDetailedMessageContainsAllParameters(): void
    {
        $exception = PermissionDeniedException::forAction('delete', 'ElementRow', 5);

        $this->assertSame('Permission denied: cannot delete ElementRow (ID 5).', $exception->getMessage());
    }

    public function testPreviousThrowablePropagates(): void
    {
        $cause = new \RuntimeException('auth failure');
        $exception = new PermissionDeniedException(
            'User msg',
            'Detail msg',
            403,
            $cause,
        );

        $this->assertSame($cause, $exception->getPrevious());
    }
}
