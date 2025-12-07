<?php

declare(strict_types=1);

namespace App\Chat\DTO;

class ChatMembersListResponse
{
    public function __construct(
        /** @var ChatMemberResponse[] */
        public readonly array $items,
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total,
    ) {}

    public function hasMore(): bool
    {
        return $this->page * $this->limit < $this->total;
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(
                fn(ChatMemberResponse $member) => $member->toArray(),
                $this->items
            ),
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'has_more' => $this->hasMore(),
        ];
    }
}
