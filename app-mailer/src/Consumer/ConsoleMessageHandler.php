<?php

declare(strict_types=1);

namespace toubilib\mailer\Consumer;

final class ConsoleMessageHandler implements MessageHandlerInterface
{
    public function handle(string $rawBody, ?array $decoded): void
    {
        echo "\n---- NEW MESSAGE ----\n";
        if ($decoded !== null) {
            echo json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } else {
            echo $rawBody;
        }
        echo "\n---------------------\n";
    }
}
