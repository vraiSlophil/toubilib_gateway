<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// définition des variables de connexion RabbitMQ

$host = getenv('RABBITMQ_HOST') ?: 'rabbitmq';
$port = (int) (getenv('RABBITMQ_PORT') ?: 5672);
$user = getenv('RABBITMQ_USER') ?: 'toubi';
$pass = getenv('RABBITMQ_PASS') ?: 'toubi';
$vhost = getenv('RABBITMQ_VHOST') ?: '/';

$exchange = getenv('RABBITMQ_EXCHANGE') ?: 'toubilib.exchange';
$routingKey = getenv('RABBITMQ_ROUTING_KEY') ?: 'toubilib.route';
$queue = getenv('RABBITMQ_QUEUE') ?: 'toubilib.queue';

//  connexion à RabbitMQ

$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel = $connection->channel();

// déclaration de l'échange et de la file d'attente, puis liaison

$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_declare($queue, false, true, false, false);
$channel->queue_bind($queue, $exchange, $routingKey);

// création du message de test

$payload = [
    'message' => 'Hello, Toubilib!',
    'timestamp' => (new DateTimeImmutable())->format(DATE_ATOM),
];

$body = json_encode($payload, JSON_UNESCAPED_SLASHES);

if ($body === false) {
    throw new RuntimeException('Failed to encode payload as JSON.');
}

// création du message AMQP

$message = new AMQPMessage($body, [
    'content_type' => 'application/json',
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
]);


// publication du message
$channel->basic_publish($message, $exchange, $routingKey);

// affichage de confirmation
echo "Message sent to {$exchange} with routing key {$routingKey}.\n";

// fermeture du canal et de la connexion
$channel->close();
$connection->close();