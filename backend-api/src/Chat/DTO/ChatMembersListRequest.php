<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ChatMembersListRequest
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 50,

        #[Assert\Length(max: 100)]
        public readonly ?string $search = null,

        #[Assert\Choice(choices: ['owner', 'admin', 'member'])]
        public readonly ?string $role = null,

        #[Assert\Choice(choices: ['available', 'busy', 'away', 'offline'])]
        public readonly ?string $status = null,

        #[Assert\Choice(choices: ['role', 'joined_at', 'name', 'online'])]
        public readonly string $sortBy = 'joined_at',

        #[Assert\Choice(choices: ['asc', 'desc'])]
        public readonly string $sortOrder = 'asc',
    ) {}

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
}
