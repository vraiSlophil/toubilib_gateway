<?php

use toubilib\api\middlewares\AuthnMiddleware;
use toubilib\api\providers\auth\JwtAuthProvider;
use toubilib\api\providers\auth\JwtManager;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\application\ports\api\providersInterfaces\JwtManagerInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\AuthRepositoryInterface;
use toubilib\core\application\usecases\AuthnService;
use toubilib\infra\adapters\MonologLogger;
use toubilib\infra\repositories\PDOAuthRepository;

return [
    // --- Adapters ---
    MonologLoggerInterface::class => static function ($c) {
        return new MonologLogger($c);
    },

    // --- Use cases ---
    AuthnService::class => static function ($c) {
        return new AuthnService($c->get(AuthRepositoryInterface::class));
    },

    // --- JWT ---
    JwtManagerInterface::class => static function ($c) {
        $jwt = $c->get('jwt');
        return new JwtManager(
            $jwt['secret'],
            $jwt['algo'],
            (int) $jwt['access_expiration'],
            (int) $jwt['refresh_expiration']
        );
    },

    AuthProviderInterface::class => static function ($c) {
        return new JwtAuthProvider(
            $c->get(AuthnService::class),
            $c->get(JwtManagerInterface::class)
        );
    },

    // --- Repositories ---
    AuthRepositoryInterface::class => static function ($c) {
        return new PDOAuthRepository(
            $c->get('db.authentification'),
        );
    },

    // --- Middlewares (utile pour l'exercice 3/4 ensuite) ---
    AuthnMiddleware::class => static function ($c) {
        return new AuthnMiddleware(
            $c->get(AuthProviderInterface::class)
        );
    },
];