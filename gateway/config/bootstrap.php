<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;
use toubilib\gateway\Middleware\AuthGatewayMiddleware;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    // Client microservice praticiens
    'client.praticiens' => function (ContainerInterface $c) {
        $baseUri = getenv('PRATICIENS_API_BASE_URI') ?: 'http://api.praticiens:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    // Client microservice patients
    'client.patients' => function (ContainerInterface $c) {
        $baseUri = getenv('PATIENTS_API_BASE_URI') ?: 'http://api.patients:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    // Client microservice authentification
    'client.auth' => function (ContainerInterface $c) {
        $baseUri = getenv('AUTH_API_BASE_URI') ?: 'http://api.auth:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    // Client API monolithique (autres routes)
    'client.api' => function (ContainerInterface $c) {
        $baseUri = getenv('MONO_API_BASE_URI') ?: 'http://api.toubilib:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    // Client microservice RDV
    'client.rdv' => function (ContainerInterface $c) {
        $baseUri = getenv('RDV_API_BASE_URI') ?: 'http://api.rdv:80/api/';
        return new Client([
            'base_uri' => rtrim($baseUri, '/') . '/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },

    AuthGatewayMiddleware::class => function (ContainerInterface $c) {
        return new AuthGatewayMiddleware($c->get('client.auth'));
    },
]);

$container = $builder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

// Journalisation des erreurs PHP
$logsDir = __DIR__ . '/../var/logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0777, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logsDir . '/errors.log');

// Middleware Slim: routing + parsing + erreurs
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMw = $app->addErrorMiddleware(true, true, true);
$errorMw->setDefaultErrorHandler(
    function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails
    ) use ($app): \Psr\Http\Message\ResponseInterface {
        $status = 500;
        if ($exception instanceof HttpException) {
            $status = $exception->getCode();
        } elseif (method_exists($exception, 'getStatusCode')) {
            $status = (int) $exception->getStatusCode();
        }
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        if ($logErrors && $status <= 500) {
            $uri = (string) $request->getUri();
            $line = sprintf(
                '[%s] %s %s %d %s (%s)',
                date('c'),
                $request->getMethod(),
                $uri,
                $status,
                $exception->getMessage(),
                $exception::class
            );
            error_log($line);
            if ($logErrorDetails) {
                error_log($exception->getTraceAsString());
            }
        }

        $message = $displayErrorDetails ? $exception->getMessage() : 'Internal server error';
        $payload = json_encode(['error' => ['message' => $message]], JSON_UNESCAPED_SLASHES);
        $response = $app->getResponseFactory()->createResponse($status);
        $response->getBody()->write($payload === false ? 'null' : $payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
);

/**
 * CORS middleware (gère aussi le préflight).
 * Note: on laisse Slim router les autres requêtes, y compris 404, puis on ajoute les headers.
 */
$app->add(function ($request, $handler) {
    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $response = new Response(204);
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});


(require __DIR__ . '/routes.php')($app);

return $app;
