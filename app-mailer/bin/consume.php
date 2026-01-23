#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use toubilib\mailer\Config\AmqpConfig;
use toubilib\mailer\Consumer\AmqpConsumer;
use toubilib\mailer\Consumer\CompositeMessageHandler;
use toubilib\mailer\Consumer\ConsoleMessageHandler;
use toubilib\mailer\Consumer\FileLoggingMessageHandler;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config', '.env');
$dotenv->safeLoad();

$logPath = getenv('MAILER_LOG_PATH') ?: 'var/logs/mailer-consumer.log';
if ($logPath !== '' && $logPath[0] !== '/') {
    $logPath = __DIR__ . '/../' . ltrim($logPath, '/');
}

$handler = new CompositeMessageHandler([
    new ConsoleMessageHandler(),
    new FileLoggingMessageHandler($logPath),
]);

$config = AmqpConfig::fromEnv();
$consumer = new AmqpConsumer($config, $handler);

try {
    $consumer->run();
} finally {
    $consumer->close();
}
