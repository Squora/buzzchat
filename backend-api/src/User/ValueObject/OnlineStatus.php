<?php

declare(strict_types=1);

namespace App\User\ValueObject;

use InvalidArgumentException;

/**
 * Online Status Value Object (Enum)
 */
enum OnlineStatus: string
{
    case AVAILABLE = 'available';
    case BUSY = 'busy';
    case AWAY = 'away';
    case OFFLINE = 'offline';

    public function getLabel(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available',
            self::BUSY => 'Busy',
            self::AWAY => 'Away',
            self::OFFLINE => 'Offline',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::AVAILABLE => '#00C853',  // Green
            self::BUSY => '#FF1744',       // Red
            self::AWAY => '#FFC107',       // Yellow
            self::OFFLINE => '#9E9E9E',    // Gray
        };
    }

    public function isOnline(): bool
    {
        return $this !== self::OFFLINE;
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $status): self
    {
        return self::tryFrom($status) ?? throw new InvalidArgumentException(
            sprintf(
                'Invalid online status: %s. Allowed values: %s',
                $status,
                implode(', ', array_column(self::cases(), 'value'))
            )
        );
    }
}
