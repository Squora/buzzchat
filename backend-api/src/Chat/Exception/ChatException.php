<?php

declare(strict_types=1);

namespace App\Chat\Exception;

use App\Shared\Exception\AbstractDomainException;

/**
 * Base exception for all Chat module exceptions
 */
abstract class ChatException extends AbstractDomainException
{
}
