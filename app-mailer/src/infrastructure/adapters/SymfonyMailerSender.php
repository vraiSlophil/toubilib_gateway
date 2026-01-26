<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\adapters;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use toubilib\mailer\application\ports\spi\EmailSenderInterface;
use toubilib\mailer\infrastructure\config\MailerConfig;

final class SymfonyMailerSender implements EmailSenderInterface
{
    private Mailer $mailer;

    public function __construct(private readonly MailerConfig $config)
    {
        $transport = Transport::fromDsn($config->dsn);
        $this->mailer = new Mailer($transport);
    }

    public function send(
        array $to,
        string $subject,
        string $textBody,
        ?string $htmlBody = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): void
    {
        if ($to === []) {
            return;
        }

        $email = new Email();

        $fromEmail = $fromEmail ?: $this->config->fromEmail;
        $fromName = $fromName ?? $this->config->fromName;
        $email->from(new Address($fromEmail, $fromName));
        $email->subject($subject);
        $email->text($textBody);
        if ($htmlBody !== null) {
            $email->html($htmlBody);
        }

        foreach ($to as $recipient) {
            $address = $recipient['email'];
            $name = $recipient['name'] ?? null;
            $email->addTo(new Address($address, $name ?? $address));
        }

        $this->mailer->send($email);
    }
}
