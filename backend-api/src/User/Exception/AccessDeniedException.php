<?php

declare(strict_types=1);

namespace App\User\Exception;

use Symfony\Component\HttpFoundation\Response;

class AccessDeniedException extends UserException
{
    public static function adminOnly(): self
    {
        return new self(
            'Only administrators can perform this action',
            Response::HTTP_FORBIDDEN
        );
    }

    public static function cannotModifyOtherUsers(): self
    {
        return new self(
            'You can only modify your own profile',
            Response::HTTP_FORBIDDEN
        );
    }
}
