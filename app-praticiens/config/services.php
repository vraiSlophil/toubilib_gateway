<?php

use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\application\usecases\AuthzService;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceIndisponibiliteInterface;
use toubilib\core\application\usecases\ServicePraticien;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\infra\adapters\AuthHeaderProvider;
use toubilib\infra\adapters\HttpRdvRepository;
use toubilib\infra\repositories\PDOPraticienRepository;
use toubilib\infra\repositories\PDOIndisponibiliteRepository;
use toubilib\infra\adapters\MonologLogger;

return [
        // --- Services ---
    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },

    AuthHeaderProvider::class => static function () {
        return new AuthHeaderProvider();
    },

    ServicePraticienInterface::class => static function ($c) {
        return new ServicePraticien(
            $c->get(PraticienRepositoryInterface::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

    AuthzService::class => static function ($c) {
        return new AuthzService($c->get(RdvRepositoryInterface::class), $c->get(MonologLoggerInterface::class));
    },

    ServiceIndisponibiliteInterface::class => static function ($c) {
        return new ServiceIndisponibilite(
            $c->get(IndisponibiliteRepositoryInterface::class),
            $c->get(RdvRepositoryInterface::class)
        );
    },

        // --- Repositories ---
    PraticienRepositoryInterface::class => static function ($c) {
        return new PDOPraticienRepository(
            $c->get('db.praticien')
        );
    },

    RdvRepositoryInterface::class => static function ($c) {
        return new HttpRdvRepository(
            $c->get('client.rdv'),
            $c->get(AuthHeaderProvider::class)
        );
    },

    IndisponibiliteRepositoryInterface::class => static function ($c) {
        return new PDOIndisponibiliteRepository(
            $c->get('db.praticien')
        );
    },
];
