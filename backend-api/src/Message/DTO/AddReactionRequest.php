<?php

declare(strict_types=1);

namespace App\Message\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AddReactionRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 20)]
    public string $emoji;
}
