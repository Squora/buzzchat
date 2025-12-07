<?php

declare(strict_types=1);

namespace App\Chat\DTO;

final readonly class ChatListResponse
{
    /**
     * @param ChatResponse[] $chats
     */
    public function __construct(
        public array $chats,
    ) {}

    public function toArray(): array
    {
        return [
            'chats' => array_map(
                fn(ChatResponse $chat) => $chat->toArray(),
                $this->chats
            ),
        ];
    }
}
