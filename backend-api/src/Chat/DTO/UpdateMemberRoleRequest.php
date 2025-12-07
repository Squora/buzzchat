<?php

declare(strict_types=1);

namespace App\Chat\DTO;

use App\Chat\Entity\ChatMember;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateMemberRoleRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[SerializedName('user_id')]
    public int $userId;

    #[Assert\NotBlank]
    #[Assert\Choice(choices: [ChatMember::ROLE_ADMIN, ChatMember::ROLE_MEMBER])]
    public string $role;
}
