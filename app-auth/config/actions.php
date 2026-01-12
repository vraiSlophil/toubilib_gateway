<?php

use toubilib\api\actions\auth\SigninAction;
use toubilib\api\actions\auth\SignupAction;
use toubilib\api\actions\auth\RefreshAction;
use toubilib\api\actions\auth\ValidateTokenAction;
use toubilib\api\actions\GetRootAction;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\application\ports\api\providersInterfaces\JwtManagerInterface;

return [
    GetRootAction::class => static function ($c) {
        return new GetRootAction();
    },

    SigninAction::class => static function ($c) {
        return new SigninAction(
            $c->get(AuthProviderInterface::class)
        );
    },

    SignupAction::class => static function ($c) {
        return new SignupAction(
            $c->get(AuthProviderInterface::class)
        );
    },

    RefreshAction::class => static function ($c) {
        return new RefreshAction(
            $c->get(JwtManagerInterface::class)
        );
    },

    ValidateTokenAction::class => static function ($c) {
        return new ValidateTokenAction(
            $c->get(JwtManagerInterface::class)
        );
    },
];