<?php

declare(strict_types=1);

namespace App\Message\DTO;

use App\Auth\DTO\UserResponse;
use App\Message\Entity\Message;

class MessageResponse
{
    public function __construct(
        public readonly int $id,
        public readonly int $chatId,
        public readonly UserResponse $user,
        public readonly string $type,
        public readonly ?string $text,
        public readonly ?array $replyTo,
        public readonly array $attachments,
        public readonly array $reactions,
        public readonly int $readCount,
        public readonly ?array $mentions,
        public readonly \DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $editedAt,
        public readonly bool $isEdited,
    ) {}

    public static function fromEntity(Message $message, bool $includeReactions = true): self
    {
        $attachments = array_map(
            fn($att) => [
                'id' => $att->getId(),
                'file_url' => $att->getFileUrl(),
                'file_name' => $att->getFileName(),
                'file_size' => $att->getFileSize(),
                'file_type' => $att->getFileType(),
                'thumbnail_url' => $att->getThumbnailUrl(),
            ],
            $message->getAttachments()->toArray()
        );

        $reactions = [];
        if ($includeReactions) {
            $reactionsByEmoji = [];
            foreach ($message->getReactions() as $reaction) {
                $emoji = $reaction->getEmoji();
                if (!isset($reactionsByEmoji[$emoji])) {
                    $reactionsByEmoji[$emoji] = [
                        'emoji' => $emoji,
                        'count' => 0,
                        'users' => [],
                    ];
                }
                $reactionsByEmoji[$emoji]['count']++;
                $reactionsByEmoji[$emoji]['users'][] = [
                    'id' => $reaction->getUser()->getId(),
                    'name' => $reaction->getUser()->getFullName(),
                ];
            }
            $reactions = array_values($reactionsByEmoji);
        }

        $replyTo = null;
        if ($message->getReplyTo()) {
            $reply = $message->getReplyTo();
            $replyTo = [
                'id' => $reply->getId(),
                'user' => [
                    'id' => $reply->getUser()->getId(),
                    'name' => $reply->getUser()->getFullName(),
                ],
                'text' => $reply->getText(),
                'type' => $reply->getType(),
            ];
        }

        return new self(
            id: $message->getId(),
            chatId: $message->getChat()->getId(),
            user: UserResponse::fromEntity($message->getUser()),
            type: $message->getType(),
            text: $message->getText(),
            replyTo: $replyTo,
            attachments: $attachments,
            reactions: $reactions,
            readCount: $message->getReadReceipts()->count(),
            mentions: $message->getMentions(),
            createdAt: $message->getCreatedAt(),
            editedAt: $message->getEditedAt(),
            isEdited: $message->isEdited(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'chat_id' => $this->chatId,
            'user' => $this->user->toArray(),
            'type' => $this->type,
            'text' => $this->text,
            'reply_to' => $this->replyTo,
            'attachments' => $this->attachments,
            'reactions' => $this->reactions,
            'read_count' => $this->readCount,
            'mentions' => $this->mentions,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'edited_at' => $this->editedAt?->format(\DateTimeInterface::ATOM),
            'is_edited' => $this->isEdited,
        ];
    }
}
