<?php

namespace toubilib\infra\adapters;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use toubilib\core\application\ports\spi\event\EventPublisherInterface;
use RuntimeException;
use DateTimeImmutable;

final class AmqpEventPublisher implements EventPublisherInterface
{
    private AMQPStreamConnection $connection;
    private string $exchange;
    /** @var array<string, string> */
    private array $routingKeys;

    /**
     * @param array<string, string> $routingKeys
     */
    public function __construct(AMQPStreamConnection $connection, string $exchange, array $routingKeys)
    {
        $this->connection = $connection;
        $this->exchange = $exchange;
        if ($routingKeys === []) {
            throw new RuntimeException('Routing key mapping cannot be empty.');
        }
        $this->routingKeys = $routingKeys;
    }
    
    public function publish(string $eventName, array $payload): void
    {
        $routingKey = $this->routingKeys[$eventName] ?? null;
        if (!is_string($routingKey) || $routingKey === '') {
            throw new RuntimeException(sprintf("No routing key configured for event '%s'.", $eventName));
        }

        $channel = $this->connection->channel();

        // Ajout d'un horodatage au payload
        $payload['event'] = $eventName;
        $payload['timestamp'] = (new DateTimeImmutable())->format(DATE_ATOM);

        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);

        if ($body === false) {
            throw new RuntimeException('Failed to encode payload as JSON.');
        }

        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);

        $channel->basic_publish($message, $this->exchange, $routingKey);

        $channel->close();
    }
}
