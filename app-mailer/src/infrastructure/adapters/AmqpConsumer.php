<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\adapters;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use toubilib\mailer\application\ports\api\MessageHandlerInterface;
use toubilib\mailer\infrastructure\config\AmqpConfig;

final class AmqpConsumer
{
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly AmqpConfig $config,
        private readonly MessageHandlerInterface $handler
    ) {
    }

    public function run(): void
    {
        while (true) {
            try {
                $this->connect();
                $this->setupTopology();
                $this->consumeLoop();
            } catch (AMQPTimeoutException) {
                continue;
            } catch (AMQPConnectionClosedException | AMQPIOException $e) {
                $this->log("Connection lost: " . $e->getMessage());
            } catch (\Throwable $e) {
                $this->log("Fatal error: " . $e->getMessage());
            } finally {
                $this->close();
            }

            sleep(max(1, $this->config->reconnectDelay));
            $this->log("Reconnecting to RabbitMQ...");
        }
    }

    public function close(): void
    {
        if ($this->channel !== null) {
            $this->channel->close();
            $this->channel = null;
        }
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    private function connect(): void
    {
        $this->connection = new AMQPStreamConnection(
            $this->config->host,
            $this->config->port,
            $this->config->user,
            $this->config->pass,
            $this->config->vhost,
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $this->config->connectionTimeout,
            $this->config->readWriteTimeout,
            null,
            false,
            $this->config->heartbeat
        );
        $this->channel = $this->connection->channel();
    }

    private function setupTopology(): void
    {
        if ($this->channel === null) {
            throw new \RuntimeException('AMQP channel is not available.');
        }

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
    }

    private function consumeLoop(): void
    {
        if ($this->channel === null) {
            throw new \RuntimeException('AMQP channel is not available.');
        }

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
            try {
                $this->channel->wait(null, false, 5);
            } catch (AMQPTimeoutException) {
                continue;
            }
        }
    }

    private function log(string $message): void
    {
        fwrite(STDERR, sprintf("[%s] %s\n", date(DATE_ATOM), $message));
    }
}
