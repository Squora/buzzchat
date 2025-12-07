<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Contracts\SmsSenderInterface;
use Psr\Log\LoggerInterface;

readonly class SmsLoggerSender implements SmsSenderInterface
{
    public function __construct(private LoggerInterface $logger) {}

    public function send(string $phone, string $message): void
    {
        $this->logger->info(sprintf('[DEV SMS] To %s: %s', $phone, $message));
    }
}
