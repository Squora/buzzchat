<?php

declare(strict_types=1);

namespace App\Message\Entity;

use App\Auth\Entity\User;
use App\Chat\Entity\Chat;
use App\Message\Repository\MessageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\Table(name: 'messages')]
#[ORM\Index(columns: ['chat_id', 'created_at'], name: 'IDX_CHAT_CREATED')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_USER')]
#[ORM\Index(columns: ['type'], name: 'IDX_TYPE')]
#[ORM\Index(columns: ['deleted_at'], name: 'IDX_DELETED')]
class Message
{
    public const TYPE_TEXT = 'text';
    public const TYPE_FILE = 'file';
    public const TYPE_IMAGE = 'image';
    public const TYPE_SYSTEM = 'system';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Chat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Chat $chat;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::TYPE_TEXT, self::TYPE_FILE, self::TYPE_IMAGE, self::TYPE_SYSTEM])]
    private string $type = self::TYPE_TEXT;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(allowNull: true)]
    private ?string $text = null;

    #[ORM\ManyToOne(targetEntity: Message::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Message $replyTo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $editedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\OneToMany(targetEntity: MessageAttachment::class, mappedBy: 'message', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $attachments;

    #[ORM\OneToMany(targetEntity: MessageReaction::class, mappedBy: 'message', cascade: ['remove'], orphanRemoval: true)]
    private Collection $reactions;

    #[ORM\OneToMany(targetEntity: MessageReadReceipt::class, mappedBy: 'message', cascade: ['remove'], orphanRemoval: true)]
    private Collection $readReceipts;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mentions = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->attachments = new ArrayCollection();
        $this->reactions = new ArrayCollection();
        $this->readReceipts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function setChat(Chat $chat): static
    {
        $this->chat = $chat;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        if (!in_array($type, [self::TYPE_TEXT, self::TYPE_FILE, self::TYPE_IMAGE, self::TYPE_SYSTEM], true)) {
            throw new \InvalidArgumentException('Invalid message type');
        }
        $this->type = $type;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function getReplyTo(): ?Message
    {
        return $this->replyTo;
    }

    public function setReplyTo(?Message $replyTo): static
    {
        $this->replyTo = $replyTo;
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

    public function getEditedAt(): ?\DateTimeInterface
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTimeInterface $editedAt): static
    {
        $this->editedAt = $editedAt;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    /**
     * @return Collection<int, MessageAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(MessageAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setMessage($this);
        }

        return $this;
    }

    public function removeAttachment(MessageAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getMessage() === $this) {
                $attachment->setMessage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MessageReaction>
     */
    public function getReactions(): Collection
    {
        return $this->reactions;
    }

    /**
     * @return Collection<int, MessageReadReceipt>
     */
    public function getReadReceipts(): Collection
    {
        return $this->readReceipts;
    }

    public function getMentions(): ?array
    {
        return $this->mentions;
    }

    public function setMentions(?array $mentions): static
    {
        $this->mentions = $mentions;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isEdited(): bool
    {
        return $this->editedAt !== null;
    }

    public function isTextMessage(): bool
    {
        return $this->type === self::TYPE_TEXT;
    }

    public function hasAttachments(): bool
    {
        return $this->attachments->count() > 0;
    }
}
