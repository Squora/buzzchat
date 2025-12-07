<?php

declare(strict_types=1);

namespace App\Auth\Service;

/**
 * Central configuration service for authentication settings
 */
final readonly class AuthConfig
{
    public function __construct(
        public int $accessTokenTtl,
        public int $refreshTokenTtl,
    ) {}
}
