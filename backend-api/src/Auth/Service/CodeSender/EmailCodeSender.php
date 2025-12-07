<?php

declare(strict_types=1);

namespace App\Auth\Service\CodeSender;

use App\Auth\Contracts\VerificationCodeSenderInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Sends verification codes via Email
 */
final readonly class EmailCodeSender implements VerificationCodeSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail = 'noreply@buzzchat.com'
    ) {}

    public function send(string $recipient, string $code): void
    {
        $email = (new Email())
            ->from($this->fromEmail)
            ->to($recipient)
            ->subject('Your Verification Code')
            ->text(sprintf('Your verification code is: %s', $code))
            ->html(sprintf('<p>Your verification code is: <strong>%s</strong></p>', $code));

        $this->mailer->send($email);
    }

    public function getChannel(): string
    {
        return 'email';
    }
}
