<?php

use toubilib\api\middlewares\AuthnMiddleware;
use toubilib\api\providers\auth\JwtAuthProvider;
use toubilib\api\providers\auth\JwtManager;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\application\ports\api\providersInterfaces\JwtManagerInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\application\usecases\AuthnService;
use toubilib\core\application\usecases\ServicePatient;
use toubilib\infra\repositories\PDOAuthRepository;
use toubilib\infra\repositories\PDOPatientRepository;
use toubilib\infra\adapters\MonologLogger;

return [
    // --- Services ---
    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },

    ServicePatientInterface::class => static function ($c) {
        return new ServicePatient(
            $c->get(PatientRepositoryInterface::class)
        );
    },

    AuthnService::class => static function ($c) {
        return new AuthnService($c->get(AuthRepositoryInterface::class));
    },

    JwtManagerInterface::class => static function ($c) {
        $jwt = $c->get('jwt');  // 👈 Récupère le tableau depuis settings.php
        return new JwtManager(
            $jwt['secret'],
            $jwt['algo'],
            (int)$jwt['access_expiration'],
            (int)$jwt['refresh_expiration']
        );
    },

    AuthProviderInterface::class => static function ($c) {
        return new JwtAuthProvider(
            $c->get(AuthnService::class),
            $c->get(JwtManagerInterface::class)
        );
    },

    // --- Repositories ---
    PatientRepositoryInterface::class => static function ($c) {
        return new PDOPatientRepository(
            $c->get('db.patient'),
        );
    },

    AuthRepositoryInterface::class => static function ($c) {
        return new PDOAuthRepository(
            $c->get('db.authentification'),
        );
    },

    // --- Middlewares ---

    AuthnMiddleware::class => function ($c) {
        return new AuthnMiddleware(
            $c->get(AuthProviderInterface::class)
        );
    },
];
