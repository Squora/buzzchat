<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class CreateGroupChatRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $name;

    #[Assert\Length(max: 500)]
    public ?string $description = null;

    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, max: 100)]
    #[SerializedName('member_ids')]
    public array $memberIds = [];
}
