<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

final class UserInactiveException extends AuthException
{
    protected int $statusCode = Response::HTTP_FORBIDDEN;
    protected string $messageKey = 'auth.user_inactive';

    public static function create(): self
    {
        return new self();
    }
}
