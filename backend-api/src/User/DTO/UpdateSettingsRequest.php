<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\UserSettings;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateSettingsRequest
{
    #[Assert\Choice(choices: [UserSettings::THEME_LIGHT, UserSettings::THEME_DARK, UserSettings::THEME_AUTO])]
    public ?string $theme = null;

    #[Assert\Type('bool')]
    #[SerializedName('notifications_enabled')]
    public ?bool $notificationsEnabled = null;

    #[Assert\Type('bool')]
    #[SerializedName('sound_enabled')]
    public ?bool $soundEnabled = null;

    #[Assert\Type('bool')]
    #[SerializedName('email_notifications')]
    public ?bool $emailNotifications = null;

    #[Assert\Type('bool')]
    #[SerializedName('show_online_status')]
    public ?bool $showOnlineStatus = null;

    #[Assert\Choice(choices: ['ru', 'en'])]
    public ?string $language = null;
}
