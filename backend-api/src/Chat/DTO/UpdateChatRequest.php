<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateChatRequest
{
    #[Assert\Length(min: 1, max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 500)]
    public ?string $description = null;

    #[Assert\Url]
    #[SerializedName('photo_url')]
    public ?string $photoUrl = null;
}
