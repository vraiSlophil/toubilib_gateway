<?php

namespace toubilib\infra\adapters;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;

final class MonologLogger implements MonologLoggerInterface
{
    private Logger $logger;

    public function __construct(ContainerInterface $c)
    {
        $logsDir = $c->get('settings')['logs_dir'];

        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0775, true);
        }

        $logger = new Logger('app');

        // Handlers
        $infoStream = new StreamHandler($logsDir . '/logs.log', Level::Debug);
        $infoFilter = new FilterHandler($infoStream, Level::Debug, Level::Info);

        $errorStream = new StreamHandler($logsDir . '/errors.log', Level::Warning);

        // Formatters
        $formatter = new LineFormatter("[ %datetime% ] %level_name%: %message% %context% %extra%\n", "Y/m/d H:i:s", true, true);
        $infoStream->setFormatter($formatter);
        $errorStream->setFormatter($formatter);

        // Pile des handlers
        $logger->pushHandler($infoFilter);
        $logger->pushHandler($errorStream);

        $this->logger = $logger;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}