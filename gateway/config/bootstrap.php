<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

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
]);

$container = $builder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();

// Middleware Slim: routing + parsing + erreurs
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMw = $app->addErrorMiddleware(true, true, true);

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