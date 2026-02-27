<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Exception;

final class HierarchyValidationException extends GridDomainException
{
    private const int STATUS_CODE = 422;

    public static function forInvalidPlacement(string $childType, string $parentType): self
    {
        return new self(
            userMessage: 'This element cannot be placed here.',
            detailedMessage: sprintf('%s cannot be placed inside %s.', $childType, $parentType),
            statusCode: self::STATUS_CODE,
        );
    }
}
