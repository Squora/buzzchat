<?php

declare(strict_types=1);

namespace App\Chat\Exception;

use Symfony\Component\HttpFoundation\Response;

class DirectChatAlreadyExistsException extends ChatException
{
    protected int $statusCode = Response::HTTP_CONFLICT;
    protected string $messageKey = 'chat.direct_already_exists';

    public static function create(int $chatId): self
    {
        $exception = new self();
        $exception->details = ['chat_id' => $chatId];
        return $exception;
    }
}
