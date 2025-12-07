<?php

declare(strict_types=1);

namespace App\Message\DTO;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

class SendMessageRequest
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[SerializedName('chat_id')]
    public int $chatId;

    #[Assert\Length(max: 10000)]
    #[SerializedName('content')]
    public ?string $text = null;

    #[Assert\Type('integer')]
    #[Assert\Positive]
    #[SerializedName('reply_to_id')]
    public ?int $replyToId = null;

    #[Assert\Type('array')]
    #[SerializedName('attachment_ids')]
    public array $attachmentIds = [];
}
