<?php

declare(strict_types=1);

namespace toubilib\mailer\Consumer;

use DateTimeImmutable;

final class FileLoggingMessageHandler implements MessageHandlerInterface
{
    public function __construct(private string $logPath)
    {
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }

    public function handle(string $rawBody, ?array $decoded): void
    {
        $timestamp = (new DateTimeImmutable())->format(DATE_ATOM);
        $payload = $decoded ?? $rawBody;
        $line = sprintf("[%s] %s\n", $timestamp, is_string($payload)
            ? $payload
            : json_encode($payload, JSON_UNESCAPED_SLASHES));

        file_put_contents($this->logPath, $line, FILE_APPEND);
    }
}
