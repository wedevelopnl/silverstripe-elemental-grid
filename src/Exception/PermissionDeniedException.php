<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Exception;

final class PermissionDeniedException extends GridDomainException
{
    private const int STATUS_CODE = 403;

    public static function forAction(string $action, string $elementType, int $elementId): self
    {
        return new self(
            userMessage: 'You do not have permission to perform this action.',
            detailedMessage: sprintf(
                'Permission denied: cannot %s %s (ID %d).',
                $action,
                $elementType,
                $elementId,
            ),
            statusCode: self::STATUS_CODE,
        );
    }
}
