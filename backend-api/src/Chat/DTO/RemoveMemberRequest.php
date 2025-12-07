<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RemoveMemberRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $userId;
}
