<?php

declare(strict_types=1);

namespace App\Chat\Exception;

use Symfony\Component\HttpFoundation\Response;

class ChatNotFoundException extends ChatException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;
    protected string $messageKey = 'chat.not_found';

    public static function withId(int $chatId): self
    {
        $exception = new self();
        $exception->details = ['chat_id' => $chatId];
        return $exception;
    }
}
