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

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);

        $app->group('/rdvs', function (RouteCollectorProxy $app) {
            $app->get('', ListRdvsAction::class);
            $app->get('/{rdvId}', GetRdvAction::class);
            $app->patch('/{rdvId}', EditRdvAction::class);
            $app->post('', CreateRdvAction::class);
            $app->delete('/{rdvId}', CancelRdvAction::class);
        });
    });

    return $app;
};