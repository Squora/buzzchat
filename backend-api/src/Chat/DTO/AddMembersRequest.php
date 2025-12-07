<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class AddMembersRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, max: 50)]
    #[SerializedName('user_ids')]
    public array $userIds = [];
}
