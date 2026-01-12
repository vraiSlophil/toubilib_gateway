<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use toubilib\gateway\Action\ProxyAction;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxyInterface $group) {
        // Liste praticiens
        $group->get('/praticiens', ProxyAction::class);
        // Détail praticien
        $group->get('/praticiens/{id}', ProxyAction::class);
        // Rdvs d’un praticien
        $group->get('/praticiens/{id}/rdvs', ProxyAction::class);
    });
};