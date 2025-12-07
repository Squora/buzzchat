<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\UserSettings;

class UserSettingsResponse
{
    public function __construct(
        public readonly string $theme,
        public readonly bool $notificationsEnabled,
        public readonly bool $soundEnabled,
        public readonly bool $emailNotifications,
        public readonly bool $showOnlineStatus,
        public readonly string $language,
    ) {}

    public static function fromEntity(UserSettings $settings): self
    {
        return new self(
            theme: $settings->getTheme(),
            notificationsEnabled: $settings->isNotificationsEnabled(),
            soundEnabled: $settings->isSoundEnabled(),
            emailNotifications: $settings->isEmailNotifications(),
            showOnlineStatus: $settings->isShowOnlineStatus(),
            language: $settings->getLanguage(),
        );
    }

    public function toArray(): array
    {
        return [
            'theme' => $this->theme,
            'notifications_enabled' => $this->notificationsEnabled,
            'sound_enabled' => $this->soundEnabled,
            'email_notifications' => $this->emailNotifications,
            'show_online_status' => $this->showOnlineStatus,
            'language' => $this->language,
        ];
    }
}
