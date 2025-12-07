<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use RuntimeException;

/**
 * Abstract base class for all domain exceptions
 * Provides common implementation of DomainExceptionInterface
 */
abstract class AbstractDomainException extends RuntimeException implements DomainExceptionInterface
{
    protected int $statusCode = 500;
    protected string $messageKey = 'error.internal_server';
    protected mixed $details = null;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }
}
