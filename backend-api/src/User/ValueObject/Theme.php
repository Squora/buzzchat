<?php

declare(strict_types=1);

namespace App\User\ValueObject;

use InvalidArgumentException;

/**
 * Theme Value Object (Enum)
 */
enum Theme: string
{
    case LIGHT = 'light';
    case DARK = 'dark';
    case AUTO = 'auto';

    public function getLabel(): string
    {
        return match($this) {
            self::LIGHT => 'Light Mode',
            self::DARK => 'Dark Mode',
            self::AUTO => 'Auto (System)',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::LIGHT => 'Always use light theme',
            self::DARK => 'Always use dark theme',
            self::AUTO => 'Follow system theme preferences',
        };
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $theme): self
    {
        return self::tryFrom($theme) ?? throw new InvalidArgumentException(
            sprintf(
                'Invalid theme: %s. Allowed values: %s',
                $theme,
                implode(', ', array_column(self::cases(), 'value'))
            )
        );
    }
}
