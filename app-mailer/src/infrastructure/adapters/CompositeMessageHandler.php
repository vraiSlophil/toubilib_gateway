<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\adapters;

use toubilib\mailer\application\ports\api\MessageHandlerInterface;

final class CompositeMessageHandler implements MessageHandlerInterface
{
    /** @var MessageHandlerInterface[] */
    private array $handlers;

    /** @param MessageHandlerInterface[] $handlers */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function handle(string $rawBody, ?array $decoded): void
    {
        foreach ($this->handlers as $handler) {
            $handler->handle($rawBody, $decoded);
        }
    }
}
