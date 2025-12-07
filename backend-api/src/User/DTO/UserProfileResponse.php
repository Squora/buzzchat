<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\Auth\Entity\User;

class UserProfileResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $photoUrl,
        public readonly ?string $position,
        public readonly ?string $statusMessage,
        public readonly string $onlineStatus,
        public readonly ?\DateTimeInterface $lastSeenAt,
        public readonly ?DepartmentResponse $department,
        public readonly array $roles,
        public readonly bool $isActive,
        public readonly \DateTimeInterface $createdAt,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            phone: $user->getPhone(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            photoUrl: $user->getPhotoUrl(),
            position: $user->getPosition(),
            statusMessage: $user->getStatusMessage(),
            onlineStatus: $user->getOnlineStatus(),
            lastSeenAt: $user->getLastSeenAt(),
            department: $user->getDepartment() ? DepartmentResponse::fromEntity($user->getDepartment()) : null,
            roles: $user->getRoles(),
            isActive: $user->isActive(),
            createdAt: $user->getCreatedAt(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->firstName . ' ' . $this->lastName,
            'photo_url' => $this->photoUrl,
            'position' => $this->position,
            'status_message' => $this->statusMessage,
            'online_status' => $this->onlineStatus,
            'last_seen_at' => $this->lastSeenAt?->format(\DateTimeInterface::ATOM),
            'department' => $this->department?->toArray(),
            'roles' => $this->roles,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
