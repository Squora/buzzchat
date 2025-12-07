<?php

declare(strict_types=1);

namespace App\Shared\DTO;

/**
 * Generic success response with a message
 */
final readonly class SuccessResponse
{
    public function __construct(
        public string $message,
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
        ];
    }
}
