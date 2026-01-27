<?php

use GuzzleHttp\Client;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\infra\adapters\MonologLogger;

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

    'cors' => [
        'allowed_origins' => explode(',', (string)env('CORS_ORIGINS', '0.0.0.0,localhost,localhost:3000,localhost:6080')),
        'allowed_methods' => explode(',', (string)env('CORS_METHODS', 'GET,POST,PUT,DELETE,OPTIONS')),
        'allowed_headers' => explode(',', (string)env('CORS_HEADERS', 'X-Requested-With,Content-Type,Accept,Origin,Authorization')),
        'exposed_headers' => explode(',', (string)env('CORS_EXPOSED_HEADERS', 'Location')),
        'allow_credentials' => env('CORS_CREDENTIALS', 'false', static fn($v) => filter_var($v, FILTER_VALIDATE_BOOLEAN)),
        'max_age' => env('CORS_MAX_AGE', 86400, static fn($v) => (int)$v),
    ],

    'db.praticien' => static function (): PDO {
        $driver = env('PRAT_DRIVER', 'pgsql');
        $host = env('PRAT_HOST', 'toubiprati.db');
        $db = env('PRAT_DATABASE', 'toubiprat');
        $user = env('PRAT_USERNAME', 'toubiprat');
        $pass = env('PRAT_PASSWORD', 'toubiprat');
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

    // Client HTTP vers microservice RDV
    'client.rdv' => static function ($c) {
        $baseUri = getenv('RDV_API_BASE_URI') ?: 'http://api.rdv:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },
];
