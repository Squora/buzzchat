<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use App\Auth\DTO\UserResponse;
use App\Chat\Entity\ChatMember;

class ChatMemberResponse
{
    public function __construct(
        public readonly int $id,
        public readonly UserResponse $user,
        public readonly string $role,
        public readonly \DateTimeInterface $joinedAt,
        public readonly ?\DateTimeInterface $leftAt = null,
    ) {}

    public static function fromEntity(ChatMember $member): self
    {
        return new self(
            id: $member->getId(),
            user: UserResponse::fromEntity($member->getUser()),
            role: $member->getRole(),
            joinedAt: $member->getJoinedAt(),
            leftAt: $member->getLeftAt(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user' => $this->user->toArray(),
            'role' => $this->role,
            'joined_at' => $this->joinedAt->format(\DateTimeInterface::ATOM),
            'left_at' => $this->leftAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
