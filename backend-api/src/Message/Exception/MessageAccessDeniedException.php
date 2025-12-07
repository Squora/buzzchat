<?php

declare(strict_types=1);

namespace App\Message\Exception;

use Symfony\Component\HttpFoundation\Response;

class MessageAccessDeniedException extends MessageException
{
    protected int $statusCode = Response::HTTP_FORBIDDEN;
    protected string $messageKey = 'message.access_denied';

    public static function cannotEditMessage(): self
    {
        $exception = new self();
        $exception->messageKey = 'message.cannot_edit';
        return $exception;
    }

    public static function cannotDeleteMessage(): self
    {
        $exception = new self();
        $exception->messageKey = 'message.cannot_delete';
        return $exception;
    }

    public static function notChatMember(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.not_member';
        return $exception;
    }
}
