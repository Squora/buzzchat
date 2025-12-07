<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

final class InvalidVerificationCodeException extends AuthException
{
    protected int $statusCode = Response::HTTP_BAD_REQUEST;
    protected string $messageKey = 'auth.verification_code_invalid';

    public static function create(): self
    {
        return new self();
    }
}
