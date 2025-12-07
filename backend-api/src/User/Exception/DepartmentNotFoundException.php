<?php

declare(strict_types=1);

namespace App\User\Exception;

use Symfony\Component\HttpFoundation\Response;

class DepartmentNotFoundException extends UserException
{
    public static function withId(int $id): self
    {
        return new self(
            "Department with ID {$id} not found",
            Response::HTTP_NOT_FOUND
        );
    }
}
