<?php

declare(strict_types=1);

namespace App\Message\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateMessageRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 10000)]
    public string $text;
}
