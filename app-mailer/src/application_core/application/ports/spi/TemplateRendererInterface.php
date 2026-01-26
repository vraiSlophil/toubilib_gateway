<?php

declare(strict_types=1);

namespace toubilib\mailer\application\ports\spi;

interface TemplateRendererInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;
}
