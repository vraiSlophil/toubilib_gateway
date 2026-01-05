<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    Client::class => function (ContainerInterface $c) {
        return new Client([
            'base_uri' => 'http://api.toubilib:80/api/',
            'http_errors' => false,
            'timeout' => 10.0,
        ]);
    },
]);

$container = $builder->build();
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

/**
 * CORS middleware
 */
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});

$app->options('/{routes:.*}', function ($request, $response) {
    return $response;
});

(require __DIR__ . '/routes.php')($app);

return $app;