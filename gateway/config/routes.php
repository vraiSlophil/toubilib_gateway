<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use toubilib\gateway\Action\GetPraticiensAction;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxyInterface $group) {
        $group->get('/praticiens', GetPraticiensAction::class);
    });
};