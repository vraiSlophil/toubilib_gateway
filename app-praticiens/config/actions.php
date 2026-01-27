<?php

use toubilib\api\actions\GetPraticienAction;
use toubilib\api\actions\ListPraticiensAction;
use toubilib\api\actions\CreatePraticienAction;
use toubilib\api\actions\UpdatePraticienAction;
use toubilib\api\actions\DeletePraticienAction;
use toubilib\api\actions\CreateIndisponibiliteAction;
use toubilib\api\actions\ListIndisponibilitesAction;
use toubilib\api\actions\DeleteIndisponibiliteAction;
use toubilib\api\actions\GetIndisponibiliteAction;
use toubilib\api\actions\UpdateIndisponibiliteAction;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
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

    CreatePraticienAction::class => static function ($c) {
        return new CreatePraticienAction(
            $c->get(ServicePraticienInterface::class)
        );
    },

    UpdatePraticienAction::class => static function ($c) {
        return new UpdatePraticienAction(
            $c->get(ServicePraticienInterface::class)
        );
    },

    DeletePraticienAction::class => static function ($c) {
        return new DeletePraticienAction(
            $c->get(ServicePraticienInterface::class)
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

    GetIndisponibiliteAction::class => static function ($c) {
        return new GetIndisponibiliteAction(
            $c->get(ServiceIndisponibilite::class)
        );
    },

    UpdateIndisponibiliteAction::class => static function ($c) {
        return new UpdateIndisponibiliteAction(
            $c->get(ServiceIndisponibilite::class)
        );
    },

];
