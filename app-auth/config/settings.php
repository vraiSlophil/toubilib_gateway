<?php

use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\infra\adapters\MonologLogger;

return [
    'settings' => [
        'displayErrorDetails' => true,
        'logError' => true,
        'logErrorDetails' => true,
        'logs_dir' => __DIR__ . '/../var/logs',
    ],

    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ORIGINS']),
        'allowed_methods' => explode(',', $_ENV['CORS_METHODS']),
        'allowed_headers' => explode(',', $_ENV['CORS_HEADERS']),
        'exposed_headers' => explode(',', $_ENV['CORS_EXPOSED_HEADERS']),
        'allow_credentials' => (bool)$_ENV['CORS_CREDENTIALS'],
        'max_age' => (int)$_ENV['CORS_MAX_AGE'],
    ],

    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'algo' => $_ENV['JWT_ALGORITHM'],
        'access_expiration' => $_ENV['JWT_ACCESS_EXPIRATION'],
        'refresh_expiration' => $_ENV['JWT_REFRESH_EXPIRATION'],
    ],


    'db.authentification' => static function (): PDO {
        $driver = $_ENV['AUTH_DRIVER'];
        $host = $_ENV['AUTH_HOST'];
        $db = $_ENV['AUTH_DATABASE'];
        $user = $_ENV['AUTH_USERNAME'];
        $pass = $_ENV['AUTH_PASSWORD'];
        $charset = 'utf8mb4';

        $dsn = $driver === 'mysql'
            ? "mysql:host={$host};dbname={$db};charset={$charset}"
            : "pgsql:host={$host};dbname={$db}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    },

    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },
];