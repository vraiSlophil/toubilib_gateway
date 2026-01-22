<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use toubilib\api\actions\EditRdvAction;
use toubilib\api\actions\GetRdvAction;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\GetRootAction;
use toubilib\api\actions\CancelRdvAction;
use toubilib\api\actions\ListRdvsAction;
use toubilib\api\middlewares\AuthzMiddleware;
use toubilib\core\application\usecases\AuthzService;
use toubilib\infra\adapters\AuthHeaderProvider;

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);

        $app->group('/rdvs', function (RouteCollectorProxy $app) {
            $c = $app->getContainer();

            $app->get('', ListRdvsAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'listRdvs', null, $c->get(AuthHeaderProvider::class)));
            $app->get('/{rdvId}', GetRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'viewRdv', null, $c->get(AuthHeaderProvider::class)));
            $app->patch('/{rdvId}', EditRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'editRdv', null, $c->get(AuthHeaderProvider::class)));
            $app->post('', CreateRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'createRdv', null, $c->get(AuthHeaderProvider::class)));
            $app->delete('/{rdvId}', CancelRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'cancelRdv', null, $c->get(AuthHeaderProvider::class)));
        });
    });

    return $app;
};
