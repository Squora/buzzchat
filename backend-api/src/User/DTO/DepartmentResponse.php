<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\Department;

class DepartmentResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly bool $isActive,
    ) {}

    public static function fromEntity(Department $department): self
    {
        return new self(
            id: $department->getId(),
            name: $department->getName(),
            description: $department->getDescription(),
            isActive: $department->isActive(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
