<?php

namespace toubilib\core\infrastructure\adapters;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use toubilib\core\application\ports\spi\event\EventPublisherInterface;
use RuntimeException;
use DateTimeImmutable;

public class AmqpEventPublisher implements EventPublisherInterface
{
    private AMQPStreamConnection $connection;
    private string $exchange;
    private string $routingKey;

    public function __construct(AMQPStreamConnection $connection, string $exchange, string $routingKey)
    {
        $this->connection = $connection;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }
    
    public function publish(string $eventName, array $payload): void
    {
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

        $channel->basic_publish($message, $this->exchange, $this->routingKey);

        $channel->close();
    }
}