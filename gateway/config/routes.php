<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use toubilib\gateway\Action\ProxyAction;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxyInterface $group) {
        // Authentification
        $group->post('/auth/signup', ProxyAction::class);
        $group->post('/auth/signin', ProxyAction::class);
        $group->post('/auth/refresh', ProxyAction::class);

        // Microservice RDV
        $group->any('/rdvs[/{rest:.*}]', ProxyAction::class);

        // Microservice praticiens
        $group->any('/praticiens[/{rest:.*}]', ProxyAction::class);

        // Reste (API monolitique)
        $group->any('[/{rest:.*}]', ProxyAction::class);
    });
};