<?php

declare(strict_types=1);

namespace toubilib\mailer\application\ports\api;

interface MessageHandlerInterface
{
    /**
     * @param array<string, mixed>|null $decoded
     */
    public function handle(string $rawBody, ?array $decoded): void;
}
