<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;
use toubilib\gateway\Action\ProxyAction;
use toubilib\gateway\Middleware\AuthGatewayMiddleware;
use toubilib\gateway\Middleware\UuidParamMiddleware;

return function (App $app): void {
    $app->group('/api', function (RouteCollectorProxyInterface $group) {
        // Authentification
        $group->group('/auth', function (RouteCollectorProxyInterface $group) {
            $group->post('/signup', ProxyAction::class);
            $group->post('/signin', ProxyAction::class);
            $group->post('/refresh', ProxyAction::class);
        });

        // Microservice RDV
        $group->group('/rdvs', function (RouteCollectorProxyInterface $rdvs) {
            $rdvs->get('', ProxyAction::class)
                ->add(AuthGatewayMiddleware::class);
            $rdvs->get('/{rdvId}', ProxyAction::class)
                ->add(AuthGatewayMiddleware::class);
            $rdvs->post('', ProxyAction::class)
                ->add(AuthGatewayMiddleware::class);
            $rdvs->patch('/{rdvId}', ProxyAction::class);
            $rdvs->delete('/{rdvId}', ProxyAction::class);
        })->add(new UuidParamMiddleware(['rdvId']));

        // Microservice praticiens
        $group->group('/praticiens', function (RouteCollectorProxyInterface $praticiens) {
            $praticiens->get('', ProxyAction::class);
            $praticiens->get('/{praticienId}', ProxyAction::class);
            $praticiens->get('/{praticienId}/rdvs', ProxyAction::class)
                ->add(AuthGatewayMiddleware::class);

            $praticiens->group('/{praticienId}/indisponibilites', function (RouteCollectorProxyInterface $indisponibilites) {
                $indisponibilites->get('', ProxyAction::class);
                $indisponibilites->post('', ProxyAction::class);
                $indisponibilites->delete('/{indispoId}', ProxyAction::class);
            });
        })->add(new UuidParamMiddleware(['praticienId', 'indispoId']));

        // Reste (API monolitique)
        $group->any('[/{rest:.*}]', ProxyAction::class);
    });
};
