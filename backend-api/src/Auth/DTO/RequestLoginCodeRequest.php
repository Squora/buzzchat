<?php

declare(strict_types=1);

namespace App\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RequestLoginCodeRequest
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\+?[0-9]{10,15}$/', message: 'Invalid phone format')]
    public string $phone;
}
