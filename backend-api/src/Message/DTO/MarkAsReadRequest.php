<?php

declare(strict_types=1);

namespace App\Message\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class MarkAsReadRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1, max: 100)]
    #[SerializedName('message_ids')]
    public array $messageIds = [];
}
