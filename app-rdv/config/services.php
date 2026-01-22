<?php

use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\application\usecases\AuthzService;
use toubilib\core\application\usecases\ServicePraticien;
use toubilib\core\application\usecases\ServiceRdv;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\infra\repositories\PDORdvRepository;
use toubilib\infra\repositories\PDOIndisponibiliteRepository;
use toubilib\infra\adapters\MonologLogger;
use GuzzleHttp\Client;
use toubilib\infra\adapters\HttpPraticienRepository;
use toubilib\infra\adapters\HttpPatientRepository;

return [
    // --- Services ---
    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },

    ServicePraticienInterface::class => static function ($c) {
        return new ServicePraticien(
            $c->get(PraticienRepositoryInterface::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

    ServiceRdvInterface::class => static function ($c) {
        return new ServiceRdv(
            $c->get(RdvRepositoryInterface::class),
            $c->get(PraticienRepositoryInterface::class),
            $c->get(PatientRepositoryInterface::class),
            $c->get(AmqpEventPublisher::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

    AuthzService::class => static function ($c) {
        return new AuthzService($c->get(RdvRepositoryInterface::class), $c->get(MonologLoggerInterface::class));
    },

    ServiceIndisponibilite::class => static function ($c) {
        return new ServiceIndisponibilite(
            $c->get(IndisponibiliteRepositoryInterface::class),
            $c->get(RdvRepositoryInterface::class)
        );
    },

    // --- Repositories ---
    PraticienRepositoryInterface::class => static function ($c) {
        return new HttpPraticienRepository(
            $c->get('client.praticiens')
        );
    },

    RdvRepositoryInterface::class => static function ($c) {
        return new PDORdvRepository(
            $c->get('db.rdv'),
        );
    },

    IndisponibiliteRepositoryInterface::class => static function ($c) {
        return new PDOIndisponibiliteRepository(
            $c->get('db.praticien')
        );
    },

    PatientRepositoryInterface::class => static function ($c) {
        return new HttpPatientRepository(
            $c->get('client.patients')
        );
    },

    AmqpEventPublisher::class => static function ($c) {
        $connection = $c->get('rabbitmq.mailer');
        $exchange = $_ENV['RABBITMQ_EXCHANGE'];
        $routingKey = $_ENV['RABBITMQ_ROUTING_KEY'];

        return new AmqpEventPublisher($connection, $exchange, $routingKey);
    },
];
