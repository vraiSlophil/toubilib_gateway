<?php

use toubilib\api\actions\ListPatientsAction;
use toubilib\api\actions\GetPatientAction;
use toubilib\api\actions\CreatePatientAction;
use toubilib\api\actions\UpdatePatientAction;
use toubilib\api\actions\DeletePatientAction;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;

return [
    ListPatientsAction::class => static function ($c) {
        return new ListPatientsAction(
            $c->get(ServicePatientInterface::class)
        );
    },

    GetPatientAction::class => static function ($c) {
        return new GetPatientAction(
            $c->get(ServicePatientInterface::class)
        );
    },

    CreatePatientAction::class => static function ($c) {
        return new CreatePatientAction(
            $c->get(ServicePatientInterface::class)
        );
    },

    UpdatePatientAction::class => static function ($c) {
        return new UpdatePatientAction(
            $c->get(ServicePatientInterface::class)
        );
    },

    DeletePatientAction::class => static function ($c) {
        return new DeletePatientAction(
            $c->get(ServicePatientInterface::class)
        );
    },

];
