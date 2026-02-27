<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Exception\ElementNotFoundException;
use WeDevelop\ElementalGrid\Exception\GridDomainException;

#[CoversClass(ElementNotFoundException::class)]
final class ElementNotFoundExceptionTest extends TestCase
{
    public function testExtendsGridDomainException(): void
    {
        $exception = ElementNotFoundException::forId(1);

        $this->assertInstanceOf(GridDomainException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testForIdStatusCode(): void
    {
        $exception = ElementNotFoundException::forId(42);

        $this->assertSame(404, $exception->getStatusCode());
    }

    public function testForIdUserMessageContainsNoId(): void
    {
        $exception = ElementNotFoundException::forId(42);

        $this->assertSame('The requested element could not be found.', $exception->getUserMessage());
        $this->assertStringNotContainsString('42', $exception->getUserMessage());
    }

    public function testForIdDetailedMessageContainsId(): void
    {
        $exception = ElementNotFoundException::forId(42);

        $this->assertSame('Element with ID 42 was not found.', $exception->getMessage());
    }

    public function testForPageStatusCode(): void
    {
        $exception = ElementNotFoundException::forPage(99);

        $this->assertSame(404, $exception->getStatusCode());
    }

    public function testForPageUserMessageContainsNoId(): void
    {
        $exception = ElementNotFoundException::forPage(99);

        $this->assertSame('No elements were found for this page.', $exception->getUserMessage());
        $this->assertStringNotContainsString('99', $exception->getUserMessage());
    }

    public function testForPageDetailedMessageContainsPageId(): void
    {
        $exception = ElementNotFoundException::forPage(99);

        $this->assertSame('No elements found for page with ID 99.', $exception->getMessage());
    }

    public function testPreviousThrowablePropagates(): void
    {
        $cause = new \RuntimeException('DB error');
        $exception = new ElementNotFoundException(
            'User msg',
            'Detail msg',
            404,
            $cause,
        );

        $this->assertSame($cause, $exception->getPrevious());
    }
}
