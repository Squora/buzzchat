<?php

declare(strict_types=1);

namespace App\Message\Exception;

use Symfony\Component\HttpFoundation\Response;

class MessageNotFoundException extends MessageException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;
    protected string $messageKey = 'message.not_found';

    public static function withId(int $id): self
    {
        $exception = new self();
        $exception->details = ['message_id' => $id];
        return $exception;
    }
}
