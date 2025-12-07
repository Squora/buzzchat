<?php

declare(strict_types=1);

namespace App\Message\DTO;

final readonly class MessageListResponse
{
    /**
     * @param MessageResponse[] $messages
     */
    public function __construct(
        public array $messages,
        public bool $hasMore,
    ) {}

    public function toArray(): array
    {
        return [
            'messages' => array_map(
                fn(MessageResponse $message) => $message->toArray(),
                $this->messages
            ),
            'has_more' => $this->hasMore,
        ];
    }
}
