<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

final class AuthDTO
{
    public function __construct(
        public ProfileDTO $profile,
        public string $access_token,
        public string $refresh_token
    ) {}
}