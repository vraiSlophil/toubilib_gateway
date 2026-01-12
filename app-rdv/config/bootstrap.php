<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/settings.php');
$builder->addDefinitions(__DIR__ . '/services.php');
$builder->addDefinitions(__DIR__ . '/actions.php');

try {
    $c = $builder->build();
} catch (Throwable $e) {
    echo "Erreur lors de la création du conteneur : " . $e->getMessage();
    exit(1);
}

AppFactory::setContainer($c);
$app = AppFactory::create();

try {
    $settings = $c->get('settings');
} catch (DependencyException $e) {
    echo "Erreur lors de la récupération des paramètres : " . $e->getMessage();
    exit(1);
} catch (NotFoundException $e) {
    echo "Paramètre 'settings' non trouvé dans le conteneur : " . $e->getMessage();
    exit(1);
}


$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();


$errorMw = $app->addErrorMiddleware(
    (bool)($settings['displayErrorDetails'] ?? true),
    (bool)($settings['logError'] ?? true),
    (bool)($settings['logErrorDetails'] ?? true)
);
$errorMw->getDefaultErrorHandler()->forceContentType('application/json');


$app = (require __DIR__ . '/../src/api/routes.php')($app);

return $app;