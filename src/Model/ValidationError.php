<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Model;

/**
 * Structured validation error with optional field context for frontend mapping.
 */
final readonly class ValidationError
{
    public function __construct(
        public string $message,
        public ?string $field = null,
        public string $type = 'error',
    ) {
    }
}
