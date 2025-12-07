<?php

declare(strict_types=1);

namespace App\User\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateProfileRequest
{
    #[Assert\Length(min: 2, max: 100)]
    #[SerializedName('first_name')]
    public ?string $firstName = null;

    #[Assert\Length(min: 2, max: 100)]
    #[SerializedName('last_name')]
    public ?string $lastName = null;

    #[Assert\Length(max: 255)]
    public ?string $position = null;

    #[Assert\Length(max: 500)]
    #[SerializedName('status_message')]
    public ?string $statusMessage = null;

    #[Assert\Url]
    #[SerializedName('photo_url')]
    public ?string $photoUrl = null;

    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[SerializedName('department_id')]
    public ?int $departmentId = null;
}
