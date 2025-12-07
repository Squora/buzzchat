<?php

declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Interface for all domain exceptions that can be automatically handled and translated
 */
interface DomainExceptionInterface
{
    /**
     * Get HTTP status code for the error response
     */
    public function getStatusCode(): int;

    /**
     * Get localization key for error message
     */
    public function getMessageKey(): string;

    /**
     * Get additional error details (optional)
     */
    public function getDetails(): mixed;
}
