<?php

declare(strict_types=1);

namespace App\Chat\ValueObject;

use InvalidArgumentException;

/**
 * Chat Member Role Value Object (Enum)
 */
enum MemberRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function getLabel(): string
    {
        return match($this) {
            self::OWNER => 'Owner',
            self::ADMIN => 'Admin',
            self::MEMBER => 'Member',
        };
    }

    public function canManageChat(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    public function canManageMembers(): bool
    {
        return $this === self::OWNER || $this === self::ADMIN;
    }

    public function canChangeRoles(): bool
    {
        return $this === self::OWNER;
    }

    public function canDeleteChat(): bool
    {
        return $this === self::OWNER;
    }

    public function getPriority(): int
    {
        return match($this) {
            self::OWNER => 3,
            self::ADMIN => 2,
            self::MEMBER => 1,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getPriority() > $other->getPriority();
    }

    /**
     * Create from string value with validation
     */
    public static function fromString(string $role): self
    {
        return self::tryFrom($role) ?? throw new InvalidArgumentException(
            sprintf(
                'Invalid member role: %s. Allowed values: %s',
                $role,
                implode(', ', array_column(self::cases(), 'value'))
            )
        );
    }
}
