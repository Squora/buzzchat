<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

use InvalidArgumentException;

/**
 * Phone Number Value Object
 * Ensures phone numbers are in E.164 format
 */
final readonly class PhoneNumber
{
    private string $value;

    public function __construct(string $phone)
    {
        $this->validate($phone);
        $this->value = $phone;
    }

    private function validate(string $phone): void
    {
        // E.164 format: +[country code][number]
        // Example: +79991234567
        if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
            throw new InvalidArgumentException(
                sprintf('Invalid phone number format: %s. Expected E.164 format (e.g., +79991234567)', $phone)
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
     * Get country code from phone number
     */
    public function getCountryCode(): string
    {
        // Extract country code (everything after + until non-digit)
        preg_match('/^\+(\d+)/', $this->value, $matches);

        return $matches[1] ?? '';
    }

    /**
     * Format for display (can be customized)
     */
    public function format(): string
    {
        // Simple formatting: +7 (999) 123-45-67
        if (str_starts_with($this->value, '+7') && strlen($this->value) === 12) {
            return sprintf(
                '+7 (%s) %s-%s-%s',
                substr($this->value, 2, 3),
                substr($this->value, 5, 3),
                substr($this->value, 8, 2),
                substr($this->value, 10, 2)
            );
        }

        return $this->value;
    }
}
