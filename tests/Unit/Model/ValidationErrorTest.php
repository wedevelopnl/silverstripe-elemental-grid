<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\ElementalGrid\Model\ValidationError;

#[CoversClass(ValidationError::class)]
final class ValidationErrorTest extends TestCase
{
    public function testConstructWithAllFields(): void
    {
        $error = new ValidationError(
            message: 'Width exceeds maximum.',
            field: 'width',
            type: 'warning',
        );

        $this->assertSame('Width exceeds maximum.', $error->message);
        $this->assertSame('width', $error->field);
        $this->assertSame('warning', $error->type);
    }

    public function testFieldDefaultsToNull(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertNull($error->field);
    }

    public function testTypeDefaultsToError(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertSame('error', $error->type);
    }
}
