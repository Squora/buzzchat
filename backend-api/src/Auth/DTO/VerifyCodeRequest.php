<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class VerifyCodeRequest
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[0-9]{10,15}$/', message: 'Invalid phone format')]
    public string $phone;

    #[Assert\NotBlank]
    #[Assert\Length(min: 4, max: 6)]
    public string $code;
}