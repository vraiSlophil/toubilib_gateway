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

    'db.praticien' => static function (): PDO {
        $driver = $_ENV['PRAT_DRIVER'];
        $host = $_ENV['PRAT_HOST'];
        $db = $_ENV['PRAT_DATABASE'];
        $user = $_ENV['PRAT_USERNAME'];
        $pass = $_ENV['PRAT_PASSWORD'];
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

    'db.rdv' => static function (): PDO {
        $driver = $_ENV['RDV_DRIVER'];
        $host = $_ENV['RDV_HOST'];
        $db = $_ENV['RDV_DATABASE'];
        $user = $_ENV['RDV_USERNAME'];
        $pass = $_ENV['RDV_PASSWORD'];
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

    'db.patient' => static function (): PDO {
        $driver = $_ENV['PAT_DRIVER'];
        $host = $_ENV['PAT_HOST'];
        $db = $_ENV['PAT_DATABASE'];
        $user = $_ENV['PAT_USERNAME'];
        $pass = $_ENV['PAT_PASSWORD'];
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