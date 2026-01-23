<?php

use GuzzleHttp\Client;
use PhpAmqpLib\Connection\AMQPStreamConnection;
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

    'db.rdv' => static function (): PDO {
        $driver = env('RDV_DRIVER', 'pgsql');
        $host = env('RDV_HOST', 'toubirdv.db');
        $db = env('RDV_DATABASE', 'toubirdv');
        $user = env('RDV_USERNAME', 'toubirdv');
        $pass = env('RDV_PASSWORD', 'toubirdv');
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

    // Client HTTP vers microservice praticiens
    'client.praticiens' => static function ($c) {
        $baseUri = getenv('PRATICIENS_API_BASE_URI') ?: 'http://api.praticiens:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    'client.patients' => static function ($c) {
        $baseUri = getenv('PATIENTS_API_BASE_URI') ?: 'http://api.patients:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    'rabbitmq.mailer' => static function () {
        $host = env('RABBITMQ_MAILER_HOST', 'rabbitmq');
        $port = env('RABBITMQ_MAILER_PORT', 5672, static fn($v) => (int)$v);
        $user = env('RABBITMQ_MAILER_USER', 'toubi');
        $pass = env('RABBITMQ_MAILER_PASS', 'toubi');
        $vhost = env('RABBITMQ_MAILER_VHOST', '/');

        return new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
    },

    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },
];
