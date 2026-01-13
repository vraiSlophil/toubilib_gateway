<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use toubilib\gateway\Action\ProxyAction;
use toubilib\gateway\Middleware\AgendaPraticienAuthMiddleware;
use toubilib\gateway\Middleware\RdvRoutesAuthMiddleware;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxyInterface $group) {
        // Authentification
        $group->post('/auth/signup', ProxyAction::class);
        $group->post('/auth/signin', ProxyAction::class);
        $group->post('/auth/refresh', ProxyAction::class);

        // Microservice RDV
        // Ex 4: le middleware n'est actif que pour POST /api/rdvs et GET /api/rdvs/{id}
        $group->any('/rdvs[/{rest:.*}]', ProxyAction::class)->add(RdvRoutesAuthMiddleware::class);

        // Microservice praticiens
        // Ex 4: le middleware n'est actif que pour GET /api/praticiens/{id}/rdvs
        $group->any('/praticiens[/{rest:.*}]', ProxyAction::class)->add(AgendaPraticienAuthMiddleware::class);

        // Reste (API monolitique)
        $group->any('[/{rest:.*}]', ProxyAction::class);
    });
};