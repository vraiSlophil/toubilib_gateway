<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use toubilib\api\actions\auth\SigninAction;
use toubilib\api\actions\auth\SignupAction;
use toubilib\api\actions\auth\RefreshAction;
use toubilib\api\actions\GetRootAction;

return function (App $app): App {
    $app->group('/api', function (RouteCollectorProxy $app) {
        // Root minimal (debug)
        $app->get('/', GetRootAction::class);

        // Auth uniquement
        $app->post('/auth/signin', SigninAction::class);
        $app->post('/auth/signup', SignupAction::class);
        $app->post('/auth/refresh', RefreshAction::class);
    });

    return $app;
};