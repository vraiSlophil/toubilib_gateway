<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\config;

final class MailerConfig
{
    public function __construct(
        public readonly string $dsn,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly string $subjectPrefix
    ) {
    }

    public static function fromEnv(): self
    {
        $dsn = getenv('MAILER_DSN') ?: 'smtp://mailcatcher:1025';
        $fromEmail = getenv('MAILER_FROM_ADDRESS') ?: 'no-reply@toubilib.local';
        $fromName = getenv('MAILER_FROM_NAME') ?: 'Toubilib';
        $subjectPrefix = getenv('MAILER_SUBJECT_PREFIX') ?: '[Toubilib]';

        return new self($dsn, $fromEmail, $fromName, $subjectPrefix);
    }
}
