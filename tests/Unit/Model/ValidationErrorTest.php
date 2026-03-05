<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Tests\Unit\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use WeDevelop\Grid\Value\ValidationSeverity;
use WeDevelop\Grid\Value\ValidationError;

#[CoversClass(ValidationError::class)]
final class ValidationErrorTest extends TestCase
{
    public function testConstructWithAllFields(): void
    {
        $error = new ValidationError(
            message: 'Width exceeds maximum.',
            field: 'width',
            severity: ValidationSeverity::Warning,
        );

        $this->assertSame('Width exceeds maximum.', $error->message);
        $this->assertSame('width', $error->field);
        $this->assertSame(ValidationSeverity::Warning, $error->severity);
    }

    public function testFieldDefaultsToNull(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertNull($error->field);
    }

    public function testSeverityDefaultsToError(): void
    {
        $error = new ValidationError(message: 'Something went wrong.');

        $this->assertSame(ValidationSeverity::Error, $error->severity);
    }
}
