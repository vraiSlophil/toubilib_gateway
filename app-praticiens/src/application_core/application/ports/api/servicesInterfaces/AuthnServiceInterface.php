<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;

interface AuthnServiceInterface
{
    public function authenticate(CredentialsDTO $credentials): ProfileDTO;
    public function register(CredentialsDTO $credentials, int $role): ProfileDTO;
}