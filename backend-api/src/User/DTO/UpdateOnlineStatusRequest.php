<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\Auth\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateOnlineStatusRequest
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [User::STATUS_AVAILABLE, User::STATUS_BUSY, User::STATUS_AWAY, User::STATUS_OFFLINE])]
    public string $status;
}
