<?php

declare(strict_types=1);

namespace App\Auth\DTO;

/**
 * Response when verification code is sent
 */
final readonly class VerificationSentResponse
{
    public function __construct(
        public string $message,
        public string $phone,
    ) {}

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'phone' => $this->phone,
        ];
    }
}
