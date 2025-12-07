<?php

declare(strict_types=1);

namespace App\Message\Entity;

use App\Auth\Entity\User;
use App\Message\Repository\MessageReadReceiptRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageReadReceiptRepository::class)]
#[ORM\Table(name: 'message_read_receipts')]
#[ORM\UniqueConstraint(name: 'UNIQ_MESSAGE_USER', columns: ['message_id', 'user_id'])]
#[ORM\Index(columns: ['message_id'], name: 'IDX_MESSAGE')]
#[ORM\Index(columns: ['user_id'], name: 'IDX_USER')]
#[ORM\Index(columns: ['read_at'], name: 'IDX_READ_AT')]
class MessageReadReceipt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Message::class, inversedBy: 'readReceipts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Message $message;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $readAt;

    public function __construct()
    {
        $this->readAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function setMessage(Message $message): static
    {
        $this->message = $message;
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

    public function getReadAt(): \DateTimeInterface
    {
        return $this->readAt;
    }

    public function setReadAt(\DateTimeInterface $readAt): static
    {
        $this->readAt = $readAt;
        return $this;
    }
}
