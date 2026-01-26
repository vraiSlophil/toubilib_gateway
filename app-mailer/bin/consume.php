#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use toubilib\mailer\infrastructure\config\AmqpConfig;
use toubilib\mailer\infrastructure\adapters\AmqpConsumer;
use toubilib\mailer\infrastructure\adapters\CompositeMessageHandler;
use toubilib\mailer\infrastructure\adapters\ConsoleMessageHandler;
use toubilib\mailer\application\usecases\EmailMessageHandler;
use toubilib\mailer\infrastructure\adapters\FileLoggingMessageHandler;
use toubilib\mailer\infrastructure\adapters\TwigTemplateRenderer;
use toubilib\mailer\infrastructure\config\MailerConfig;
use toubilib\mailer\infrastructure\adapters\SymfonyMailerSender;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../config', '.env');
$dotenv->safeLoad();

$logPath = getenv('MAILER_LOG_PATH') ?: 'var/logs/mailer-consumer.log';
if ($logPath !== '' && $logPath[0] !== '/') {
    $logPath = __DIR__ . '/../' . ltrim($logPath, '/');
}

$mailerConfig = MailerConfig::fromEnv();
$mailerSender = new SymfonyMailerSender($mailerConfig);
$templatePath = getenv('MAILER_TEMPLATE_PATH') ?: __DIR__ . '/../templates';
if ($templatePath !== '' && $templatePath[0] !== '/') {
    $templatePath = __DIR__ . '/../' . ltrim($templatePath, '/');
}
$renderer = new TwigTemplateRenderer($templatePath);

$handler = new CompositeMessageHandler([
    new ConsoleMessageHandler(),
    new FileLoggingMessageHandler($logPath),
    new EmailMessageHandler($mailerSender, $renderer, $mailerConfig),
]);

$config = AmqpConfig::fromEnv();
$consumer = new AmqpConsumer($config, $handler);

try {
    $consumer->run();
} finally {
    $consumer->close();
}
