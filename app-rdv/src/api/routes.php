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

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);

        $app->group('/rdvs', function (RouteCollectorProxy $app) {
            $c = $app->getContainer();

            $app->get('', ListRdvsAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'listRdvs'));
            $app->get('/{rdvId}', GetRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'viewRdv'));
            $app->patch('/{rdvId}', EditRdvAction::class);
            $app->post('', CreateRdvAction::class)
                ->add(new AuthzMiddleware($c->get(AuthzService::class), 'createRdv'));
            $app->delete('/{rdvId}', CancelRdvAction::class);
        });
    });

    return $app;
};
