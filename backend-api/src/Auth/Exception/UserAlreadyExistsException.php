<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

final class UserAlreadyExistsException extends AuthException
{
    protected int $statusCode = Response::HTTP_CONFLICT;
    protected string $messageKey = 'auth.user_already_exists';

    public static function withPhone(string $phone): self
    {
        $exception = new self();
        $exception->details = ['phone' => $phone];
        return $exception;
    }

    public static function withEmail(string $email): self
    {
        $exception = new self();
        $exception->details = ['email' => $email];
        return $exception;
    }
}
