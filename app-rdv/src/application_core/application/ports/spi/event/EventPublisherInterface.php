<?php

namespace toubilib\core\application\ports\spi\event;

interface EventPublisherInterface
{
    public function publish(string $eventName, array $payload): void;
}