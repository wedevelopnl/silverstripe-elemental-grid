<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Exception;

final class InvalidGridValueException extends GridDomainException
{
    private const int STATUS_CODE = 422;

    public static function forWidth(int $width, int $maxColumns): self
    {
        return new self(
            userMessage: 'The specified column width is invalid.',
            detailedMessage: sprintf('Width %d exceeds maximum of %d columns.', $width, $maxColumns),
            statusCode: self::STATUS_CODE,
        );
    }

    public static function forOffset(int $offset, int $maxColumns): self
    {
        return new self(
            userMessage: 'The specified column offset is invalid.',
            detailedMessage: sprintf('Offset %d exceeds maximum of %d columns.', $offset, $maxColumns),
            statusCode: self::STATUS_CODE,
        );
    }

    public static function forViewport(string $key): self
    {
        return new self(
            userMessage: 'The specified viewport is not recognised.',
            detailedMessage: sprintf('Viewport key "%s" is not a valid breakpoint.', $key),
            statusCode: self::STATUS_CODE,
        );
    }

    public static function forColumnCount(mixed $value): self
    {
        return new self(
            userMessage: 'The configured column count is invalid.',
            detailedMessage: sprintf(
                'Column count must be a positive integer, got %s (%s).',
                var_export($value, true),
                get_debug_type($value),
            ),
            statusCode: self::STATUS_CODE,
        );
    }
}
