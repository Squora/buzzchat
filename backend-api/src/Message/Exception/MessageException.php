<?php

declare(strict_types=1);

namespace App\Message\Exception;

use App\Shared\Exception\AbstractDomainException;

/**
 * Base exception for all Message module exceptions
 */
abstract class MessageException extends AbstractDomainException
{
}
