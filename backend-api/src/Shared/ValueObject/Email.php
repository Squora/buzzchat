<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use InvalidArgumentException;

/**
 * Email Value Object
 * Ensures emails are valid
 */
final readonly class Email
{
    private string $value;

    public function __construct(string $email)
    {
        $normalized = strtolower(trim($email));
        $this->validate($normalized);
        $this->value = $normalized;
    }

    private function validate(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                sprintf('Invalid email address: %s', $email)
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Get domain part of email
     */
    public function getDomain(): string
    {
        $parts = explode('@', $this->value);

        return $parts[1] ?? '';
    }

    /**
     * Get local part of email (before @)
     */
    public function getLocalPart(): string
    {
        $parts = explode('@', $this->value);

        return $parts[0] ?? '';
    }

    /**
     * Check if email belongs to a specific domain
     */
    public function isFromDomain(string $domain): bool
    {
        return $this->getDomain() === strtolower($domain);
    }
}
