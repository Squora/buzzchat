<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class CreateDirectChatRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[SerializedName('user_id')]
    public int $userId;
}
