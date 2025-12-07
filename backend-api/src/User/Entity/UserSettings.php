<?php

declare(strict_types=1);

namespace App\User\Entity;

use App\Auth\Entity\User;
use App\User\Repository\UserSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserSettingsRepository::class)]
#[ORM\Table(name: 'user_settings')]
class UserSettings
{
    public const THEME_LIGHT = 'light';
    public const THEME_DARK = 'dark';
    public const THEME_AUTO = 'auto';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: [self::THEME_LIGHT, self::THEME_DARK, self::THEME_AUTO])]
    private string $theme = self::THEME_AUTO;

    #[ORM\Column]
    private bool $notificationsEnabled = true;

    #[ORM\Column]
    private bool $soundEnabled = true;

    #[ORM\Column]
    private bool $emailNotifications = true;

    #[ORM\Column]
    private bool $showOnlineStatus = true;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: ['ru', 'en'])]
    private string $language = 'ru';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        if (!in_array($theme, [self::THEME_LIGHT, self::THEME_DARK, self::THEME_AUTO], true)) {
            throw new \InvalidArgumentException('Invalid theme');
        }
        $this->theme = $theme;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isNotificationsEnabled(): bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(bool $notificationsEnabled): static
    {
        $this->notificationsEnabled = $notificationsEnabled;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isSoundEnabled(): bool
    {
        return $this->soundEnabled;
    }

    public function setSoundEnabled(bool $soundEnabled): static
    {
        $this->soundEnabled = $soundEnabled;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(bool $emailNotifications): static
    {
        $this->emailNotifications = $emailNotifications;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function isShowOnlineStatus(): bool
    {
        return $this->showOnlineStatus;
    }

    public function setShowOnlineStatus(bool $showOnlineStatus): static
    {
        $this->showOnlineStatus = $showOnlineStatus;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;
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
}
