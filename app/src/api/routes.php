<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use toubilib\api\actions\auth\SigninAction;
use toubilib\api\actions\auth\SignupAction;
use toubilib\api\actions\EditRdvAction;
use toubilib\api\actions\GetPraticienAction;
use toubilib\api\actions\GetRdvAction;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\GetRootAction;
use toubilib\api\actions\ListBookedSlotsAction;
use toubilib\api\actions\ListPraticiensAction;
use toubilib\api\actions\CancelRdvAction;
use toubilib\api\actions\AgendaPraticienAction;
use toubilib\api\actions\ListRdvsAction;
use toubilib\api\actions\CreateIndisponibiliteAction;
use toubilib\api\actions\ListIndisponibilitesAction;
use toubilib\api\actions\DeleteIndisponibiliteAction;
use toubilib\api\middlewares\AuthnMiddleware;
use toubilib\api\middlewares\AuthzMiddleware;
use toubilib\core\application\usecases\AuthzService;

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        $app->get('/', GetRootAction::class);
        $app->post('/auth/signin', SigninAction::class);
        $app->post('/auth/signup', SignupAction::class);


        $app->group('/praticiens', function (RouteCollectorProxy $app) {
            $app->get('', ListPraticiensAction::class);
            $app->group('/{praticienId}', function (RouteCollectorProxy $app) {
                $app->get('', GetPraticienAction::class);
                $app->get('/rdvs', ListBookedSlotsAction::class)
//                    ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'viewAgenda'))
//                    ->add(AuthnMiddleware::class)
                ;

                // Routes for indisponibilites
                $app->get('/indisponibilites', ListIndisponibilitesAction::class)
//                    ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'manageIndisponibilites'))
//                    ->add(AuthnMiddleware::class)
                ;
                $app->post('/indisponibilites', CreateIndisponibiliteAction::class)
//                    ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'manageIndisponibilites'))
//                    ->add(AuthnMiddleware::class)
                ;
                $app->delete('/indisponibilites/{indispo_id}', DeleteIndisponibiliteAction::class)
//                    ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'manageIndisponibilites'))
//                    ->add(AuthnMiddleware::class)
                ;
            });
        });


        $app->group('/rdvs', function (RouteCollectorProxy $app) {
            $app->get('', ListRdvsAction::class)
//                ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'listRdvs'))
//                ->add(AuthnMiddleware::class)
            ;
//            $app->get('/history', ListRdvsAction::class)
//                ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'listRdvs'))
//                ->add(AuthnMiddleware::class);
            $app->get('/{rdvId}', GetRdvAction::class)
//                ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'viewRdv'))
//                ->add(AuthnMiddleware::class)
            ;
            $app->patch('/{rdvId}', EditRdvAction::class)
//                ->add(new AuthzMiddleware($app->getContainer()->get(AuthzService::class), 'editRdv'))
//                ->add(AuthnMiddleware::class)
            ;
            $app->group('', function (RouteCollectorProxy $app) {
                $c = $app->getContainer();
                $app->post('', CreateRdvAction::class)
//                    ->add(new AuthzMiddleware($c->get(AuthzService::class), 'createRdv'))
                ;
                $app->delete('/{rdvId}', CancelRdvAction::class)
//                    ->add(new AuthzMiddleware($c->get(AuthzService::class), 'cancelRdv'))
                ;
            })
//                ->add(AuthnMiddleware::class)
            ;
        });
//        })->add(AuthnMiddleware::class);
    });

    return $app;
};