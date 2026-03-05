<?php

declare(strict_types=1);

namespace WeDevelop\Grid\Model;

use WeDevelop\Grid\Contract\ValidationSeverity;

/**
 * Structured validation error with optional field context for frontend mapping.
 */
final readonly class ValidationError
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public ValidationSeverity $severity = ValidationSeverity::Error,
    ) {
    }
}
