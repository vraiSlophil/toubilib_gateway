<?php

namespace toubilib\core\application\ports\spi\adapterInterface;

interface MonologLoggerInterface
{

    public function debug(string $message, array $context = []): void;

    public function log(string $level, string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;

}