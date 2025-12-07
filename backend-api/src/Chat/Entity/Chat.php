<?php

declare(strict_types=1);

namespace App\Chat\Entity;

use App\Auth\Entity\User;
use App\Chat\Repository\ChatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChatRepository::class)]
#[ORM\Table(name: 'chats')]
#[ORM\Index(columns: ['type'], name: 'IDX_TYPE')]
#[ORM\Index(columns: ['created_at'], name: 'IDX_CREATED_AT')]
class Chat
{
    public const TYPE_DIRECT = 'direct';
    public const TYPE_GROUP = 'group';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_DIRECT, self::TYPE_GROUP])]
    private string $type;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: ChatMember::class, mappedBy: 'chat', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_DIRECT, self::TYPE_GROUP], true)) {
            throw new \InvalidArgumentException('Invalid chat type');
        }
        $this->type = $type;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return Collection<int, ChatMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ChatMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setChat($this);
        }

        return $this;
    }

    public function removeMember(ChatMember $member): static
    {
        if ($this->members->removeElement($member)) {
            if ($member->getChat() === $this) {
                $member->setChat(null);
            }
        }

        return $this;
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function hasMember(int $userId): bool
    {
        foreach ($this->members as $member) {
            if ($member->getUser()->getId() === $userId) {
                return true;
            }
        }
        return false;
    }

    public function getMemberByUserId(int $userId): ?ChatMember
    {
        foreach ($this->members as $member) {
            if ($member->getUser()->getId() === $userId) {
                return $member;
            }
        }
        return null;
    }

    public function getMembersCount(): int
    {
        return $this->members->count();
    }

    /**
     * Get companion user for direct chat (the other user, not the current one)
     * Returns null for group chats or if companion not found
     */
    public function getCompanion(User $currentUser): ?User
    {
        if (!$this->isDirect()) {
            return null;
        }

        foreach ($this->members as $member) {
            if ($member->getUser()->getId() !== $currentUser->getId()) {
                return $member->getUser();
            }
        }

        return null;
    }
}
