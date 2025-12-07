<?php

declare(strict_types=1);

namespace App\Chat\Entity;

use App\Auth\Entity\User;
use App\Chat\Repository\ChatMemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChatMemberRepository::class)]
#[ORM\Table(name: 'chat_members')]
#[ORM\UniqueConstraint(name: 'UNIQ_CHAT_USER', columns: ['chat_id', 'user_id'])]
#[ORM\Index(columns: ['chat_id', 'left_at'], name: 'IDX_CHAT_ACTIVE')]
#[ORM\Index(columns: ['chat_id', 'role', 'left_at'], name: 'IDX_CHAT_ROLE_ACTIVE')]
#[ORM\Index(columns: ['chat_id', 'joined_at'], name: 'IDX_CHAT_JOINED')]
#[ORM\Index(columns: ['role'], name: 'IDX_ROLE')]
#[ORM\Index(columns: ['joined_at'], name: 'IDX_JOINED_AT')]
#[ORM\Index(columns: ['left_at'], name: 'IDX_LEFT_AT')]
class ChatMember
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chat::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Chat $chat = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MEMBER])]
    private string $role = self::ROLE_MEMBER;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $joinedAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $leftAt = null;

    public function __construct()
    {
        $this->joinedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): ?Chat
    {
        return $this->chat;
    }

    public function setChat(?Chat $chat): static
    {
        $this->chat = $chat;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (!in_array($role, [self::ROLE_OWNER, self::ROLE_ADMIN, self::ROLE_MEMBER], true)) {
            throw new \InvalidArgumentException('Invalid role');
        }
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): \DateTimeInterface
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeInterface $joinedAt): static
    {
        $this->joinedAt = $joinedAt;
        return $this;
    }

    public function getLeftAt(): ?\DateTimeInterface
    {
        return $this->leftAt;
    }

    public function setLeftAt(?\DateTimeInterface $leftAt): static
    {
        $this->leftAt = $leftAt;
        return $this;
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }

    public function canManageChat(): bool
    {
        return $this->isOwner() || $this->isAdmin();
    }

    public function hasLeft(): bool
    {
        return $this->leftAt !== null;
    }
}
