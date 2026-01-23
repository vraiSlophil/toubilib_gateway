<?php

if (!function_exists('env')) {
    function env(string $key, mixed $default = null, ?callable $cast = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $default;
        }
        if ($cast !== null) {
            return $cast($value);
        }
        return $value;
    }
}

return [

    'settings' => [
        'displayErrorDetails' => true,
        'logError' => true,
        'logErrorDetails' => true,
        'logs_dir' => __DIR__ . '/../var/logs',
    ],

    'db.patient' => static function (): PDO {
        $driver = env('PAT_DRIVER', 'pgsql');
        $host = env('PAT_HOST', 'toubipat.db');
        $db = env('PAT_DATABASE', 'toubipat');
        $user = env('PAT_USERNAME', 'toubipat');
        $pass = env('PAT_PASSWORD', 'toubipat');
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
