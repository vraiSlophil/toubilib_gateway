<?php

declare(strict_types=1);

namespace toubilib\mailer\infrastructure\config;

final class AmqpConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $user,
        public readonly string $pass,
        public readonly string $vhost,
        public readonly string $exchange,
        public readonly string $exchangeType,
        public readonly string $queue,
        /** @var string[] */
        public readonly array $bindings,
        public readonly int $prefetch,
        public readonly bool $queueDurable,
        public readonly int $heartbeat,
        public readonly int $reconnectDelay,
        public readonly float $connectionTimeout,
        public readonly float $readWriteTimeout
    ) {
    }

    public static function fromEnv(): self
    {
        $bindings = self::parseCsv(getenv('RABBITMQ_BINDINGS') ?: getenv('RABBITMQ_ROUTING_KEYS') ?: '');
        if ($bindings === []) {
            $bindings = array_values(array_unique(array_filter([
                getenv('RABBITMQ_ROUTING_KEY_MAIL_CREATED') ?: null,
                getenv('RABBITMQ_ROUTING_KEY_MAIL_CANCELLED') ?: null,
                getenv('RABBITMQ_ROUTING_KEY') ?: null,
            ], static fn(?string $value) => $value !== null && trim($value) !== '')));
        }

        $queueDurable = self::parseBool(getenv('RABBITMQ_QUEUE_DURABLE') ?: 'false');
        $heartbeat = (int) (getenv('RABBITMQ_HEARTBEAT') ?: 30);
        $reconnectDelay = (int) (getenv('RABBITMQ_RECONNECT_DELAY') ?: 5);
        $connectionTimeout = (float) (getenv('RABBITMQ_CONNECTION_TIMEOUT') ?: 3.0);
        $readWriteTimeout = (float) (getenv('RABBITMQ_READ_WRITE_TIMEOUT') ?: 3.0);

        return new self(
            host: getenv('RABBITMQ_MAILER_HOST') ?: (getenv('RABBITMQ_HOST') ?: 'rabbitmq'),
            port: (int) (getenv('RABBITMQ_MAILER_PORT') ?: (getenv('RABBITMQ_PORT') ?: 5672)),
            user: getenv('RABBITMQ_MAILER_USER') ?: (getenv('RABBITMQ_USER') ?: 'toubi'),
            pass: getenv('RABBITMQ_MAILER_PASS') ?: (getenv('RABBITMQ_PASS') ?: 'toubi'),
            vhost: getenv('RABBITMQ_MAILER_VHOST') ?: (getenv('RABBITMQ_VHOST') ?: '/'),
            exchange: getenv('RABBITMQ_EXCHANGE') ?: 'rdv.events',
            exchangeType: getenv('RABBITMQ_EXCHANGE_TYPE') ?: 'direct',
            queue: getenv('RABBITMQ_QUEUE') ?: 'rdv.mail.queue',
            bindings: $bindings,
            prefetch: (int) (getenv('RABBITMQ_PREFETCH') ?: 1),
            queueDurable: $queueDurable,
            heartbeat: $heartbeat,
            reconnectDelay: $reconnectDelay,
            connectionTimeout: $connectionTimeout,
            readWriteTimeout: $readWriteTimeout
        );
    }

    /** @return string[] */
    private static function parseCsv(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $items = array_map('trim', explode(',', $value));
        $items = array_filter($items, static fn(string $item) => $item !== '');
        return array_values(array_unique($items));
    }

    private static function parseBool(string $value): bool
    {
        $normalized = strtolower(trim($value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    }
}
