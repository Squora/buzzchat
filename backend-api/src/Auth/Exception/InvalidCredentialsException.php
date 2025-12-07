<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

final class InvalidCredentialsException extends AuthException
{
    protected int $statusCode = Response::HTTP_UNAUTHORIZED;
    protected string $messageKey = 'auth.invalid_credentials';

    public static function create(): self
    {
        return new self();
    }
}
