<?php

declare(strict_types=1);

namespace toubilib\mailer\Consumer;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use toubilib\mailer\Config\AmqpConfig;

final class AmqpConsumer
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(
        private readonly AmqpConfig $config,
        private readonly MessageHandlerInterface $handler
    ) {
        $this->connection = new AMQPStreamConnection(
            $config->host,
            $config->port,
            $config->user,
            $config->pass,
            $config->vhost
        );
        $this->channel = $this->connection->channel();
    }

    public function run(): void
    {
        $this->channel->basic_qos(null, $this->config->prefetch, null);

        $this->channel->exchange_declare(
            $this->config->exchange,
            $this->config->exchangeType,
            false,
            true,
            false
        );

        $this->channel->queue_declare(
            $this->config->queue,
            false,
            $this->config->queueDurable,
            false,
            false
        );

        foreach ($this->config->bindings as $binding) {
            $this->channel->queue_bind($this->config->queue, $this->config->exchange, $binding);
        }

        echo " [*] Waiting for messages on {$this->config->queue}. To exit press CTRL+C\n";

        $callback = function (AMQPMessage $msg): void {
            $raw = $msg->getBody();
            $decoded = json_decode($raw, true);
            $this->handler->handle($raw, is_array($decoded) ? $decoded : null);
            $msg->ack();
        };

        $this->channel->basic_consume(
            $this->config->queue,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function close(): void
    {
        $this->channel->close();
        $this->connection->close();
    }
}
