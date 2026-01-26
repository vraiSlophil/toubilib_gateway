<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\adapters;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use toubilib\mailer\application\ports\spi\TemplateRendererInterface;

final class TwigTemplateRenderer implements TemplateRendererInterface
{
    private Environment $twig;

    public function __construct(string $templatePath)
    {
        $loader = new FilesystemLoader($templatePath);
        $this->twig = new Environment($loader, [
            'autoescape' => 'html',
        ]);
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($template, $context);
    }
}
