<?php

declare(strict_types=1);

namespace App\Chat\ValueObject;

use InvalidArgumentException;

/**
 * Chat Type Value Object (Enum)
 */
enum ChatType: string
{
    case DIRECT = 'direct';
    case GROUP = 'group';

    public function getLabel(): string
    {
        return match($this) {
            self::DIRECT => 'Direct Message',
            self::GROUP => 'Group Chat',
        };
    }

    public function isDirect(): bool
    {
        return $this === self::DIRECT;
    }

    public function isGroup(): bool
    {
        return $this === self::GROUP;
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $type): self
    {
        return self::tryFrom($type) ?? throw new InvalidArgumentException(
            sprintf(
                'Invalid chat type: %s. Allowed values: %s',
                $type,
                implode(', ', array_column(self::cases(), 'value'))
            )
        );
    }
}
