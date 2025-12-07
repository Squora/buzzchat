<?php

declare(strict_types=1);

namespace App\Chat\Exception;

use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends ChatException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;
    protected string $messageKey = 'user.not_found';

    public static function withId(int $userId): self
    {
        $exception = new self();
        $exception->details = ['user_id' => $userId];
        return $exception;
    }

    public static function withIds(array $userIds): self
    {
        $exception = new self();
        $exception->details = ['user_ids' => $userIds];
        return $exception;
    }
}
