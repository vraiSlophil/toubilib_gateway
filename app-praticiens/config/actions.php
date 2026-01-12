<?php

use toubilib\api\actions\auth\SigninAction;
use toubilib\api\actions\auth\SignupAction;
use toubilib\api\actions\AgendaPraticienAction;
use toubilib\api\actions\CancelRdvAction;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\GetPraticienAction;
use toubilib\api\actions\GetRdvAction;
use toubilib\api\actions\ListPraticiensAction;
use toubilib\api\actions\ListRdvsAction;
use toubilib\api\actions\CreateIndisponibiliteAction;
use toubilib\api\actions\ListIndisponibilitesAction;
use toubilib\api\actions\DeleteIndisponibiliteAction;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\usecases\ServiceIndisponibilite;

return [
    ListPraticiensAction::class => static function ($c) {
        return new ListPraticiensAction(
            $c->get(ServicePraticienInterface::class)
        );
    },

    GetPraticienAction::class => static function ($c) {
        return new GetPraticienAction(
            $c->get(ServicePraticienInterface::class)
        );
    },

    AgendaPraticienAction::class => static function ($c) {
        return new AgendaPraticienAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    ListRdvsAction::class => static function ($c) {
        return new ListRdvsAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    CreateRdvAction::class => static function ($c) {
        return new CreateRdvAction(
            $c->get(ServiceRdvInterface::class),
        );
    },

    GetRdvAction::class => static function ($c) {
        return new GetRdvAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    CancelRdvAction::class => static function ($c) {
        return new CancelRdvAction(
            $c->get(ServiceRdvInterface::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

    SigninAction::class => static function ($c) {
        return new SigninAction(
            $c->get(AuthProviderInterface::class)
        );
    },

    SignupAction::class => static function ($c) {
        return new SignupAction(
            $c->get(AuthProviderInterface::class)
        );
    },

    CreateIndisponibiliteAction::class => static function ($c) {
        return new CreateIndisponibiliteAction(
            $c->get(ServiceIndisponibilite::class)
        );
    },

    ListIndisponibilitesAction::class => static function ($c) {
        return new ListIndisponibilitesAction(
            $c->get(ServiceIndisponibilite::class)
        );
    },

    DeleteIndisponibiliteAction::class => static function ($c) {
        return new DeleteIndisponibiliteAction(
            $c->get(ServiceIndisponibilite::class)
        );
    },

];