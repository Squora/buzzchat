<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\HttpFoundation\Response;

final class VerificationNotFoundException extends AuthException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;
    protected string $messageKey = 'auth.verification_not_found';

    public static function forPhone(string $phone): self
    {
        $exception = new self();
        $exception->details = ['phone' => $phone];
        return $exception;
    }
}
