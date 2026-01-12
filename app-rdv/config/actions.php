<?php

use toubilib\api\actions\EditRdvAction;
use toubilib\api\actions\GetRdvAction;
use toubilib\api\actions\CreateRdvAction;
use toubilib\api\actions\GetRootAction;
use toubilib\api\actions\CancelRdvAction;
use toubilib\api\actions\ListRdvsAction;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;

return [
    GetRootAction::class => static function ($c) {
        return new GetRootAction();
    },

    ListRdvsAction::class => static function ($c) {
        return new ListRdvsAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    GetRdvAction::class => static function ($c) {
        return new GetRdvAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    CreateRdvAction::class => static function ($c) {
        return new CreateRdvAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    EditRdvAction::class => static function ($c) {
        return new EditRdvAction(
            $c->get(ServiceRdvInterface::class)
        );
    },

    CancelRdvAction::class => static function ($c) {
        return new CancelRdvAction(
            $c->get(ServiceRdvInterface::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

];