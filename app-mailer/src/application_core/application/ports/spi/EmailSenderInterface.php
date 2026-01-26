<?php

declare(strict_types=1);

namespace toubilib\mailer\application\ports\spi;

interface EmailSenderInterface
{
    /**
     * @param array<int, array{email:string, name?:string|null}> $to
     */
    public function send(
        array $to,
        string $subject,
        string $textBody,
        ?string $htmlBody = null,
        ?string $fromEmail = null,
        ?string $fromName = null
    ): void;
}
