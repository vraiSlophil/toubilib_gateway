<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'toubi';
$pass = getenv('RABBITMQ_PASS') ?: 'toubi';
$vhost = getenv('RABBITMQ_VHOST') ?: '/';

$exchange = getenv('RABBITMQ_EXCHANGE') ?: 'rdv.events';
$routingKey = getenv('RABBITMQ_ROUTING_KEY') ?: 'rdv.created';
$queue = getenv('RABBITMQ_QUEUE') ?: 'notif.mail';

$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel = $connection->channel();

$channel->exchange_declare($exchange, 'topic', false, true, false);
$channel->queue_declare($queue, false, true, false, false);
$channel->queue_bind($queue, $exchange, 'rdv.created');
$channel->queue_bind($queue, $exchange, 'rdv.cancelled');

$payload = [
    'event' => 'RDV.CREATED',
    'rdv' => [
        'id' => 'test-id',
        'praticienId' => 'praticien-1',
        'patientId' => 'patient-1',
        'patientEmail' => 'patient@example.com',
        'dateDebut' => (new DateTimeImmutable())->format(DATE_ATOM),
        'dureeMinutes' => 30,
    ],
    'recipient' => [
        'type' => 'mail',
        'email' => 'patient@example.com',
    ],
];

$body = json_encode($payload, JSON_UNESCAPED_SLASHES);
if ($body === false) {
    throw new RuntimeException('Failed to encode payload as JSON.');
}

$message = new AMQPMessage($body, [
    'content_type' => 'application/json',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);

$channel->basic_publish($message, $exchange, $routingKey);

echo "Message sent to {$exchange} with routing key {$routingKey}.\n";

$channel->close();
$connection->close();
