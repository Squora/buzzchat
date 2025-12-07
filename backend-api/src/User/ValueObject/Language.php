<?php

declare(strict_types=1);

namespace App\User\ValueObject;

use InvalidArgumentException;

/**
 * Language Value Object (Enum)
 */
enum Language: string
{
    case RUSSIAN = 'ru';
    case ENGLISH = 'en';

    public function getLabel(): string
    {
        return match($this) {
            self::RUSSIAN => 'Русский',
            self::ENGLISH => 'English',
        };
    }

    public function getLocale(): string
    {
        return match($this) {
            self::RUSSIAN => 'ru_RU',
            self::ENGLISH => 'en_US',
        };
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $language): self
    {
        return self::tryFrom($language) ?? throw new InvalidArgumentException(
            sprintf(
                'Invalid language: %s. Allowed values: %s',
                $language,
                implode(', ', array_column(self::cases(), 'value'))
            )
        );
    }

    /**
     * Get default language
     */
    public static function default(): self
    {
        return self::ENGLISH;
    }
}
