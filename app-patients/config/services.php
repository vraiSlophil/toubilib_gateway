<?php

use toubilib\api\middlewares\AuthnMiddleware;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\application\usecases\ServicePatient;
use toubilib\infra\repositories\PDOPatientRepository;

return [
    ServicePatientInterface::class => static function ($c) {
        return new ServicePatient(
            $c->get(PatientRepositoryInterface::class)
        );
    },

    PatientRepositoryInterface::class => static function ($c) {
        return new PDOPatientRepository(
            $c->get('db.patient'),
        );
    },

    AuthnMiddleware::class => static function () {
        return new AuthnMiddleware();
    },
];
