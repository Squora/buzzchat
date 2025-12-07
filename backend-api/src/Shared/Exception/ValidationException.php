<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Exception thrown when request validation fails
 */
final class ValidationException extends AbstractDomainException
{
    protected int $statusCode = Response::HTTP_BAD_REQUEST;
    protected string $messageKey = 'validation.failed';

    public function __construct(
        private readonly ConstraintViolationListInterface $violations
    ) {
        $messages = [];
        $errors = [];

        foreach ($this->violations as $violation) {
            $field = $violation->getPropertyPath();
            $message = $violation->getMessage();

            $messages[] = $field . ': ' . $message;
            $errors[$field] = $message;
        }

        parent::__construct(implode(', ', $messages));
        $this->details = $errors;
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
