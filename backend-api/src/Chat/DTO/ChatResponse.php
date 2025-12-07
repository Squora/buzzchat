<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use App\Auth\Entity\User;
use App\Chat\Entity\Chat;

class ChatResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $type,
        public readonly ?string $name,
        public readonly ?string $photoUrl,
        public readonly int $membersCount,
        public readonly \DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
        public readonly ?string $currentUserRole = null,
        public readonly ?array $membersPreviews = null,
    ) {}

    /**
     * Create response from entity
     *
     * @param Chat $chat
     * @param User|null $currentUser Current authenticated user (required for direct chats)
     * @param bool $includePreviews Include first few members for preview (max 5)
     */
    public static function fromEntity(Chat $chat, ?User $currentUser = null, bool $includePreviews = false): self
    {
        $currentUserRole = null;
        if ($currentUser) {
            $member = $chat->getMemberByUserId($currentUser->getId());
            $currentUserRole = $member?->getRole();
        }

        // For direct chats: use companion's name and photo
        $name = $chat->getName();
        $photoUrl = $chat->getPhotoUrl();

        if ($chat->isDirect() && $currentUser) {
            $companion = $chat->getCompanion($currentUser);
            if ($companion) {
                $name = $companion->getName();
                $photoUrl = $companion->getPhotoUrl();
            }
        }

        // For previews
        $membersPreviews = null;
        if ($includePreviews) {
            if ($chat->isDirect() && $currentUser) {
                // For direct chats: show only companion (not current user)
                $companion = $chat->getCompanion($currentUser);
                if ($companion) {
                    $membersPreviews = [
                        [
                            'id' => $companion->getId(),
                            'name' => $companion->getName(),
                            'photo_url' => $companion->getPhotoUrl(),
                        ]
                    ];
                }
            } else {
                // For group chats: show first 5 members
                $members = $chat->getMembers()->slice(0, 5);
                $membersPreviews = array_map(
                    fn($member) => [
                        'id' => $member->getUser()->getId(),
                        'name' => $member->getUser()->getName(),
                        'photo_url' => $member->getUser()->getPhotoUrl(),
                    ],
                    $members
                );
            }
        }

        return new self(
            id: $chat->getId(),
            type: $chat->getType(),
            name: $name,
            photoUrl: $photoUrl,
            membersCount: $chat->getMembersCount(),
            createdAt: $chat->getCreatedAt(),
            updatedAt: $chat->getUpdatedAt(),
            currentUserRole: $currentUserRole,
            membersPreviews: $membersPreviews,
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'photo_url' => $this->photoUrl,
            'members_count' => $this->membersCount,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];

        if ($this->currentUserRole !== null) {
            $data['current_user_role'] = $this->currentUserRole;
        }

        if ($this->membersPreviews !== null) {
            $data['members_previews'] = $this->membersPreviews;
        }

        return $data;
    }
}
