<?php

declare(strict_types=1);

namespace App\Auth\Service\CodeSender;

use App\Auth\Contracts\VerificationCodeSenderInterface;
use Psr\Log\LoggerInterface;

/**
 * Sends verification codes to application logs (for development/testing)
 */
final readonly class LogCodeSender implements VerificationCodeSenderInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function send(string $recipient, string $code): void
    {
        $this->logger->info(
            sprintf('[VERIFICATION CODE] Recipient: %s | Code: %s', $recipient, $code),
            ['channel' => $this->getChannel(), 'recipient' => $recipient, 'code' => $code]
        );
    }

    public function getChannel(): string
    {
        return 'log';
    }
}
