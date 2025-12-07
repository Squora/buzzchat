<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use App\Auth\Entity\User;

class UserResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly array $roles,
        public readonly bool $isActive,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            email: $user->getEmail(),
            phone: $user->getPhone(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            roles: $user->getRoles(),
            isActive: $user->isActive(),
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
            'roles' => $this->roles,
            'is_active' => $this->isActive,
        ];
    }
}
