<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use App\Shared\Exception\AbstractDomainException;

/**
 * Base exception for all Auth module exceptions
 */
abstract class AuthException extends AbstractDomainException
{
}
