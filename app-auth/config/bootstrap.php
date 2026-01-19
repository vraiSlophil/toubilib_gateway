<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Dotenv\Dotenv;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpException;

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

$logsDir = $settings['logs_dir'] ?? (__DIR__ . '/../var/logs');
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0777, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logsDir . '/errors.log');

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();


$errorMw = $app->addErrorMiddleware(
    (bool)($settings['displayErrorDetails'] ?? true),
    (bool)($settings['logError'] ?? true),
    (bool)($settings['logErrorDetails'] ?? true)
);
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


$app = (require __DIR__ . '/../src/api/routes.php')($app);

return $app;
