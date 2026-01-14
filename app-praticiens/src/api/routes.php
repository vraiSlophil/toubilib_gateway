<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use toubilib\api\actions\GetRootAction;
use toubilib\api\actions\GetPraticienAction;
use toubilib\api\actions\ListBookedSlotsAction;
use toubilib\api\actions\ListPraticiensAction;
use toubilib\api\actions\CreateIndisponibiliteAction;
use toubilib\api\actions\ListIndisponibilitesAction;
use toubilib\api\actions\DeleteIndisponibiliteAction;
use toubilib\api\middlewares\AuthzMiddleware;
use toubilib\core\application\usecases\AuthzService;

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);

        $app->group('/praticiens', function (RouteCollectorProxy $app) {
            $app->get('', ListPraticiensAction::class);
            $app->group('/{praticienId}', function (RouteCollectorProxy $app) {
                $app->get('', GetPraticienAction::class);
                $app->get('/rdvs', ListBookedSlotsAction::class)
                    ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'viewAgenda'));

                $app->get('/indisponibilites', ListIndisponibilitesAction::class);
                $app->post('/indisponibilites', CreateIndisponibiliteAction::class);
                $app->delete('/indisponibilites/{indispoId}', DeleteIndisponibiliteAction::class);
            });
        });
    });

    return $app;
};
