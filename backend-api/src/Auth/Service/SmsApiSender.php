<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Contracts\SmsSenderInterface;

class SmsApiSender implements SmsSenderInterface
{

    public function send(string $phone, string $message): void
    {
        // TODO: Implement send() method.
    }
}