<?php

declare(strict_types=1);

namespace App\Auth\Service\CodeSender;

use App\Auth\Contracts\SmsSenderInterface;
use App\Auth\Contracts\VerificationCodeSenderInterface;

/**
 * Sends verification codes via SMS
 */
final readonly class SmsCodeSender implements VerificationCodeSenderInterface
{
    public function __construct(
        private SmsSenderInterface $smsSender
    ) {}

    public function send(string $recipient, string $code): void
    {
        $message = sprintf('Your verification code: %s', $code);
        $this->smsSender->send($recipient, $message);
    }

    public function getChannel(): string
    {
        return 'sms';
    }
}
