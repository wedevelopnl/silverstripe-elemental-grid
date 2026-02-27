<?php

declare(strict_types=1);

namespace WeDevelop\ElementalGrid\Exception;

/**
 * Base exception for all elemental grid domain errors.
 * Carries a user-safe message (no IDs or internals) and an HTTP status code.
 */
abstract class GridDomainException extends \RuntimeException
{
    private readonly string $userMessage;
    private readonly int $statusCode;

    public function __construct(
        string $userMessage,
        string $detailedMessage,
        int $statusCode,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($detailedMessage, 0, $previous);

        $this->userMessage = $userMessage;
        $this->statusCode = $statusCode;
    }

    /** Safe for API responses — contains no IDs or internals. */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
