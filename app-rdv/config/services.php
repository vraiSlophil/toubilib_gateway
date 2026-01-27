<?php

use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\application\usecases\AuthzService;
use toubilib\core\application\usecases\ServicePraticien;
use toubilib\core\application\usecases\ServiceRdv;
use toubilib\infra\repositories\PDORdvRepository;
use toubilib\infra\adapters\MonologLogger;
use toubilib\infra\adapters\AuthHeaderProvider;
use toubilib\infra\adapters\AmqpEventPublisher;
use toubilib\infra\adapters\HttpIndisponibiliteRepository;
use toubilib\infra\adapters\HttpPraticienRepository;
use toubilib\infra\adapters\HttpPatientRepository;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null, ?callable $cast = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            $value = $default;
        }
        if ($cast !== null) {
            return $cast($value);
        }
        return $value;
    }
}

return [
        // --- Services ---
    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },

    AuthHeaderProvider::class => static function () {
        return new AuthHeaderProvider();
    },

    ServiceRdvInterface::class => static function ($c) {
        return new ServiceRdv(
            $c->get(RdvRepositoryInterface::class),
            $c->get(PraticienRepositoryInterface::class),
            $c->get(PatientRepositoryInterface::class),
            $c->get(IndisponibiliteRepositoryInterface::class),
            $c->get(AmqpEventPublisher::class),
            $c->get(MonologLoggerInterface::class)
        );
    },

    AuthzService::class => static function ($c) {
        return new AuthzService($c->get(RdvRepositoryInterface::class), $c->get(MonologLoggerInterface::class));
    },

        // --- Repositories ---
    PraticienRepositoryInterface::class => static function ($c) {
        return new HttpPraticienRepository(
            $c->get('client.praticiens'),
            $c->get(AuthHeaderProvider::class)
        );
    },

    RdvRepositoryInterface::class => static function ($c) {
        return new PDORdvRepository(
            $c->get('db.rdv'),
        );
    },

    IndisponibiliteRepositoryInterface::class => static function ($c) {
        return new HttpIndisponibiliteRepository(
            $c->get('client.praticiens'),
            $c->get(AuthHeaderProvider::class)
        );
    },

    PatientRepositoryInterface::class => static function ($c) {
        return new HttpPatientRepository(
            $c->get('client.patients'),
            $c->get(AuthHeaderProvider::class)
        );
    },

    AmqpEventPublisher::class => static function ($c) {
        $connection = $c->get('rabbitmq.mailer');
        $exchange = env('RABBITMQ_EXCHANGE', 'rdv.events');
        $routingKeys = [
            'rdv.created' => env('RABBITMQ_ROUTING_KEY_MAIL_CREATED', 'rdv.created'),
            'rdv.cancelled' => env('RABBITMQ_ROUTING_KEY_MAIL_CANCELLED', 'rdv.cancelled'),
        ];

        return new AmqpEventPublisher($connection, $exchange, $routingKeys);
    },
];
