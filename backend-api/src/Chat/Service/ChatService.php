<?php

declare(strict_types=1);

namespace App\Chat\Service;

use App\Auth\Entity\User;
use App\Auth\Repository\UserRepository;
use App\Chat\DTO\AddMembersRequest;
use App\Chat\DTO\ChatMembersListRequest;
use App\Chat\DTO\CreateDirectChatRequest;
use App\Chat\DTO\CreateGroupChatRequest;
use App\Chat\DTO\UpdateChatRequest;
use App\Chat\DTO\UpdateMemberRoleRequest;
use App\Chat\Entity\Chat;
use App\Chat\Entity\ChatMember;
use App\Chat\Exception\ChatAccessDeniedException;
use App\Chat\Exception\ChatNotFoundException;
use App\Chat\Exception\DirectChatAlreadyExistsException;
use App\Chat\Exception\InvalidChatOperationException;
use App\Chat\Exception\UserNotFoundException;
use App\Chat\Repository\ChatMemberRepository;
use App\Chat\Repository\ChatRepository;

final class ChatService
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly ChatMemberRepository $chatMemberRepository,
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Create a new group chat
     */
    public function createGroupChat(CreateGroupChatRequest $dto, User $creator): Chat
    {
        // Validate that all users exist
        $memberUsers = $this->validateAndGetUsers($dto->memberIds);

        $chat = new Chat();
        $chat->setType(Chat::TYPE_GROUP);
        $chat->setName($dto->name);
        $chat->setDescription($dto->description);

        // Add creator as owner
        $ownerMember = new ChatMember();
        $ownerMember->setChat($chat);
        $ownerMember->setUser($creator);
        $ownerMember->setRole(ChatMember::ROLE_OWNER);
        $chat->addMember($ownerMember);

        // Add other members
        foreach ($memberUsers as $user) {
            if ($user->getId() === $creator->getId()) {
                continue; // Skip creator as already added
            }

            $member = new ChatMember();
            $member->setChat($chat);
            $member->setUser($user);
            $member->setRole(ChatMember::ROLE_MEMBER);
            $chat->addMember($member);
        }

        $this->chatRepository->save($chat);

        return $chat;
    }

    /**
     * Create or get existing direct chat
     */
    public function createOrGetDirectChat(CreateDirectChatRequest $dto, User $currentUser): Chat
    {
        if ($dto->userId === $currentUser->getId()) {
            throw new InvalidChatOperationException('Cannot create direct chat with yourself');
        }

        $otherUser = $this->userRepository->find($dto->userId);
        if (!$otherUser) {
            throw UserNotFoundException::withId($dto->userId);
        }

        // Check if direct chat already exists
        $existingChat = $this->chatRepository->findDirectChatBetweenUsers(
            $currentUser->getId(),
            $otherUser->getId()
        );

        if ($existingChat) {
            return $existingChat;
        }

        // Create new direct chat
        $chat = new Chat();
        $chat->setType(Chat::TYPE_DIRECT);

        // Add both users as members
        $member1 = new ChatMember();
        $member1->setChat($chat);
        $member1->setUser($currentUser);
        $member1->setRole(ChatMember::ROLE_MEMBER);
        $chat->addMember($member1);

        $member2 = new ChatMember();
        $member2->setChat($chat);
        $member2->setUser($otherUser);
        $member2->setRole(ChatMember::ROLE_MEMBER);
        $chat->addMember($member2);

        $this->chatRepository->save($chat);

        return $chat;
    }

    /**
     * Update chat (name, description, photo)
     */
    public function updateChat(int $chatId, UpdateChatRequest $dto, User $currentUser): Chat
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        $member = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$member || !$member->canManageChat()) {
            throw ChatAccessDeniedException::forAction('update chat');
        }

        if ($dto->name !== null) {
            $chat->setName($dto->name);
        }

        if ($dto->description !== null) {
            $chat->setDescription($dto->description);
        }

        if ($dto->photoUrl !== null) {
            $chat->setPhotoUrl($dto->photoUrl);
        }

        $this->chatRepository->save($chat);

        return $chat;
    }

    /**
     * Add members to group chat
     */
    public function addMembers(int $chatId, AddMembersRequest $dto, User $currentUser): Chat
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        $member = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$member || !$member->canManageChat()) {
            throw ChatAccessDeniedException::forAction('add members');
        }

        $newUsers = $this->validateAndGetUsers($dto->userIds);

        foreach ($newUsers as $user) {
            if ($chat->hasMember($user->getId())) {
                continue; // Skip if already a member
            }

            $newMember = new ChatMember();
            $newMember->setChat($chat);
            $newMember->setUser($user);
            $newMember->setRole(ChatMember::ROLE_MEMBER);
            $chat->addMember($newMember);
        }

        $this->chatRepository->save($chat);

        return $chat;
    }

    /**
     * Remove member from chat
     */
    public function removeMember(int $chatId, int $userId, User $currentUser): Chat
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        if ($userId === $currentUser->getId()) {
            throw InvalidChatOperationException::cannotRemoveSelf();
        }

        $currentMember = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$currentMember || !$currentMember->canManageChat()) {
            throw ChatAccessDeniedException::forAction('remove members');
        }

        $targetMember = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $userId);
        if (!$targetMember) {
            throw InvalidChatOperationException::userNotMember($userId);
        }

        if ($targetMember->isOwner()) {
            throw InvalidChatOperationException::cannotRemoveOwner();
        }

        $chat->removeMember($targetMember);
        $this->chatMemberRepository->remove($targetMember);

        return $chat;
    }

    /**
     * Leave chat
     */
    public function leaveChat(int $chatId, User $currentUser): void
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        $member = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$member) {
            throw InvalidChatOperationException::userNotMember($currentUser->getId());
        }

        if ($member->isOwner()) {
            throw new InvalidChatOperationException('Owner cannot leave the chat. Please transfer ownership first or delete the chat.');
        }

        $chat->removeMember($member);
        $this->chatMemberRepository->remove($member);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(int $chatId, UpdateMemberRoleRequest $dto, User $currentUser): Chat
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        if ($dto->userId === $currentUser->getId()) {
            throw InvalidChatOperationException::cannotChangeOwnRole();
        }

        $currentMember = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$currentMember || !$currentMember->isOwner()) {
            throw ChatAccessDeniedException::forAction('change member roles');
        }

        $targetMember = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $dto->userId);
        if (!$targetMember) {
            throw InvalidChatOperationException::userNotMember($dto->userId);
        }

        if ($targetMember->isOwner()) {
            throw InvalidChatOperationException::cannotChangeOwnerRole();
        }

        $targetMember->setRole($dto->role);
        $this->chatMemberRepository->save($targetMember);

        return $chat;
    }

    /**
     * Delete chat
     */
    public function deleteChat(int $chatId, User $currentUser): void
    {
        $chat = $this->getChatWithAccessCheck($chatId, $currentUser);

        if ($chat->isDirect()) {
            throw InvalidChatOperationException::cannotModifyDirectChat();
        }

        $member = $this->chatMemberRepository->findActiveByChatAndUser($chatId, $currentUser->getId());
        if (!$member || !$member->isOwner()) {
            throw ChatAccessDeniedException::forAction('delete chat');
        }

        $this->chatRepository->remove($chat);
    }

    /**
     * Get user chats
     */
    public function getUserChats(User $user): array
    {
        return $this->chatRepository->findUserChats($user->getId());
    }

    /**
     * Get chat by ID with access check
     */
    public function getChat(int $chatId, User $currentUser): Chat
    {
        return $this->getChatWithAccessCheck($chatId, $currentUser);
    }

    /**
     * Get chat members with pagination and filters
     *
     * @return array{items: ChatMember[], total: int}
     */
    public function getChatMembers(int $chatId, ChatMembersListRequest $request, User $currentUser): array
    {
        // Check access to chat
        $this->getChatWithAccessCheck($chatId, $currentUser);

        // Get members with pagination
        return $this->chatMemberRepository->findMembersWithPagination(
            chatId: $chatId,
            offset: $request->getOffset(),
            limit: $request->limit,
            search: $request->search,
            role: $request->role,
            status: $request->status,
            sortBy: $request->sortBy,
            sortOrder: $request->sortOrder
        );
    }

    /**
     * Helper: Get chat and check user has access
     */
    private function getChatWithAccessCheck(int $chatId, User $user): Chat
    {
        $chat = $this->chatRepository->find($chatId);
        if (!$chat) {
            throw ChatNotFoundException::withId($chatId);
        }

        if (!$chat->hasMember($user->getId())) {
            throw ChatAccessDeniedException::create();
        }

        return $chat;
    }

    /**
     * Helper: Validate and get users by IDs
     */
    private function validateAndGetUsers(array $userIds): array
    {
        $users = $this->userRepository->findBy(['id' => $userIds]);

        $foundIds = array_map(fn(User $u) => $u->getId(), $users);
        $missingIds = array_diff($userIds, $foundIds);

        if (count($missingIds) > 0) {
            throw UserNotFoundException::withIds($missingIds);
        }

        return $users;
    }
}
