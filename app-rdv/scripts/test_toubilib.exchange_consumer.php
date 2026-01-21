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

$channel->queue_declare($queue, false, true, false, false);

echo "Waiting for messages in {$queue}. To exit press CTRL+C\n";

// consommation des messages

$channel->basic_consume(
    $queue,
    '',
    false,
    true,
    false,
    false,
    function (AMQPMessage $message) {
        $body = $message->getBody();
        $data = json_decode($body, true);

        echo $data['message'] . " (received at " . $data['timestamp'] . ")\n";
    }
);

// boucle d'attente des messages

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
