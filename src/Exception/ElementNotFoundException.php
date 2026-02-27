<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Exception;

final class ElementNotFoundException extends GridDomainException
{
    private const int STATUS_CODE = 404;

    public static function forId(int $id): self
    {
        return new self(
            userMessage: 'The requested element could not be found.',
            detailedMessage: sprintf('Element with ID %d was not found.', $id),
            statusCode: self::STATUS_CODE,
        );
    }

    public static function forPage(int $pageId): self
    {
        return new self(
            userMessage: 'No elements were found for this page.',
            detailedMessage: sprintf('No elements found for page with ID %d.', $pageId),
            statusCode: self::STATUS_CODE,
        );
    }
}
