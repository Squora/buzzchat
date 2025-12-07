<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank]
    #[SerializedName('first_name')]
    public string $firstName;

    #[Assert\NotBlank]
    #[SerializedName('last_name')]
    public string $lastName;

    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[0-9]{10,15}$/', message: 'Invalid phone format')]
    public string $phone;
}
