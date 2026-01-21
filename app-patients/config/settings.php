<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'logError' => true,
        'logErrorDetails' => true,
        'logs_dir' => __DIR__ . '/../var/logs',
    ],

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
];
