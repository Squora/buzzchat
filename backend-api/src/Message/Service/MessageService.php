<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Auth\Entity\User;
use App\Chat\Repository\ChatMemberRepository;
use App\Chat\Repository\ChatRepository;
use App\Message\DTO\SendMessageRequest;
use App\Message\DTO\UpdateMessageRequest;
use App\Message\Entity\Message;
use App\Message\Entity\MessageReaction;
use App\Message\Exception\MessageAccessDeniedException;
use App\Message\Exception\MessageNotFoundException;
use App\Message\Repository\MessageReactionRepository;
use App\Message\Repository\MessageReadReceiptRepository;
use App\Message\Repository\MessageRepository;

final class MessageService
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly MessageReactionRepository $reactionRepository,
        private readonly MessageReadReceiptRepository $readReceiptRepository,
        private readonly ChatRepository $chatRepository,
        private readonly ChatMemberRepository $chatMemberRepository,
        private readonly MentionService $mentionService,
    ) {}

    /**
     * Send a new message
     */
    public function sendMessage(SendMessageRequest $dto, User $user): Message
    {
        $chat = $this->chatRepository->find($dto->chatId);
        if (!$chat) {
            throw new \RuntimeException("Chat with ID {$dto->chatId} not found");
        }

        // Check if user is member of chat
        if (!$this->chatMemberRepository->isMemberOfChat($dto->chatId, $user->getId())) {
            throw MessageAccessDeniedException::notChatMember();
        }

        $message = new Message();
        $message->setChat($chat);
        $message->setUser($user);
        $message->setText($dto->text);
        $message->setType(Message::TYPE_TEXT);

        // Handle reply
        if ($dto->replyToId) {
            $replyTo = $this->messageRepository->find($dto->replyToId);
            if ($replyTo && $replyTo->getChat()->getId() === $dto->chatId) {
                $message->setReplyTo($replyTo);
            }
        }

        // Extract and save mentions
        if ($dto->text) {
            $mentions = $this->mentionService->extractMentions($dto->text);
            if (!empty($mentions)) {
                $message->setMentions($mentions);
            }
        }

        // Handle attachments (if provided)
        // TODO: Implement attachment handling when FileUploadService is ready

        $this->messageRepository->save($message);

        return $message;
    }

    /**
     * Update message text
     */
    public function updateMessage(int $messageId, UpdateMessageRequest $dto, User $user): Message
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw MessageNotFoundException::withId($messageId);
        }

        if ($message->getUser()->getId() !== $user->getId()) {
            throw MessageAccessDeniedException::cannotEditMessage();
        }

        if ($message->isDeleted()) {
            throw new \RuntimeException('Cannot edit deleted message');
        }

        $message->setText($dto->text);
        $message->setEditedAt(new \DateTime());
        $message->setUpdatedAt(new \DateTime());

        // Re-extract mentions
        $mentions = $this->mentionService->extractMentions($dto->text);
        $message->setMentions(!empty($mentions) ? $mentions : null);

        $this->messageRepository->save($message);

        return $message;
    }

    /**
     * Delete message (soft delete)
     */
    public function deleteMessage(int $messageId, User $user): Message
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw MessageNotFoundException::withId($messageId);
        }

        if ($message->getUser()->getId() !== $user->getId()) {
            throw MessageAccessDeniedException::cannotDeleteMessage();
        }

        $message->setDeletedAt(new \DateTime());
        $message->setUpdatedAt(new \DateTime());
        $this->messageRepository->save($message);

        return $message;
    }

    /**
     * Add reaction to message
     */
    public function addReaction(int $messageId, string $emoji, User $user): void
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw MessageNotFoundException::withId($messageId);
        }

        // Check if user is member of chat
        if (!$this->chatMemberRepository->isMemberOfChat($message->getChat()->getId(), $user->getId())) {
            throw MessageAccessDeniedException::notChatMember();
        }

        // Check if reaction already exists
        $existing = $this->reactionRepository->findByMessageUserEmoji(
            $messageId,
            $user->getId(),
            $emoji
        );

        if ($existing) {
            return; // Already reacted with this emoji
        }

        $reaction = new MessageReaction();
        $reaction->setMessage($message);
        $reaction->setUser($user);
        $reaction->setEmoji($emoji);

        $this->reactionRepository->save($reaction);
    }

    /**
     * Remove reaction from message
     */
    public function removeReaction(int $messageId, string $emoji, User $user): void
    {
        $reaction = $this->reactionRepository->findByMessageUserEmoji(
            $messageId,
            $user->getId(),
            $emoji
        );

        if ($reaction) {
            $this->reactionRepository->remove($reaction);
        }
    }

    /**
     * Mark messages as read
     */
    public function markAsRead(array $messageIds, User $user): void
    {
        $this->readReceiptRepository->markAsRead($messageIds, $user->getId());
    }

    /**
     * Get messages for chat with pagination
     */
    public function getMessages(int $chatId, User $user, ?int $beforeId = null, int $limit = 50): array
    {
        // Check if user is member of chat
        if (!$this->chatMemberRepository->isMemberOfChat($chatId, $user->getId())) {
            throw MessageAccessDeniedException::notChatMember();
        }

        return $this->messageRepository->findByChatId($chatId, $beforeId, $limit);
    }

    /**
     * Get unread count for chat
     */
    public function getUnreadCount(int $chatId, User $user): int
    {
        return $this->messageRepository->countUnread($chatId, $user->getId());
    }

    /**
     * Search messages
     */
    public function searchMessages(string $query, ?int $chatId, User $user, int $limit = 50): array
    {
        // If chatId provided, check membership
        if ($chatId !== null) {
            if (!$this->chatMemberRepository->isMemberOfChat($chatId, $user->getId())) {
                throw MessageAccessDeniedException::notChatMember();
            }
        }

        return $this->messageRepository->search($query, $chatId, $limit);
    }

    /**
     * Get mentions for user
     */
    public function getMentions(User $user, int $limit = 50): array
    {
        return $this->messageRepository->findMentions($user->getId(), $limit);
    }

    /**
     * Get message by ID
     */
    public function getMessage(int $messageId, User $user): Message
    {
        $message = $this->messageRepository->find($messageId);
        if (!$message) {
            throw MessageNotFoundException::withId($messageId);
        }

        // Check if user is member of chat
        if (!$this->chatMemberRepository->isMemberOfChat($message->getChat()->getId(), $user->getId())) {
            throw MessageAccessDeniedException::notChatMember();
        }

        return $message;
    }
}
