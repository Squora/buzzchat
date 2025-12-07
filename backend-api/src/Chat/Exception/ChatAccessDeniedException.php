<?php

declare(strict_types=1);

namespace App\Chat\Exception;

use Symfony\Component\HttpFoundation\Response;

class ChatAccessDeniedException extends ChatException
{
    protected int $statusCode = Response::HTTP_FORBIDDEN;
    protected string $messageKey = 'chat.access_denied';

    public static function create(): self
    {
        return new self();
    }

    public static function forAction(string $action): self
    {
        $exception = new self();
        $exception->details = ['action' => $action];
        return $exception;
    }
}
