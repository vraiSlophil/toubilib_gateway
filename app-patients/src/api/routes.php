<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use toubilib\api\actions\GetRootAction;
use toubilib\api\actions\ListPatientsAction;
use toubilib\api\actions\GetPatientAction;
use toubilib\api\actions\CreatePatientAction;
use toubilib\api\actions\UpdatePatientAction;
use toubilib\api\actions\DeletePatientAction;
use toubilib\api\middlewares\AuthnMiddleware;

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);

        $app->group('/patients', function (RouteCollectorProxy $app) {
            $app->get('', ListPatientsAction::class);
            $app->post('', CreatePatientAction::class);
            $app->group('/{patientId}', function (RouteCollectorProxy $app) {
                $app->get('', GetPatientAction::class);
                $app->put('', UpdatePatientAction::class);
                $app->delete('', DeletePatientAction::class);
            });
        })->add(AuthnMiddleware::class);
    });

    return $app;
};
