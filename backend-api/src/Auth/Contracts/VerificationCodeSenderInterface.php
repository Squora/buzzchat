<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

/**
 * Interface for sending verification codes through different channels
 */
interface VerificationCodeSenderInterface
{
    /**
     * Send verification code to the recipient
     *
     * @param string $recipient Recipient identifier (phone, email, etc.)
     * @param string $code Verification code
     * @return void
     */
    public function send(string $recipient, string $code): void;

    /**
     * Get the channel name (sms, email, log, etc.)
     *
     * @return string
     */
    public function getChannel(): string;
}
