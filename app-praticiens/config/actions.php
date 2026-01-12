<?php

use toubilib\api\actions\GetPraticienAction;
use toubilib\api\actions\ListPraticiensAction;
use toubilib\api\actions\ListBookedSlotsAction;
use toubilib\api\actions\CreateIndisponibiliteAction;
use toubilib\api\actions\ListIndisponibilitesAction;
use toubilib\api\actions\DeleteIndisponibiliteAction;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
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

    ListBookedSlotsAction::class => static function ($c) {
        return new ListBookedSlotsAction(
            $c->get(ServiceRdvInterface::class)
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