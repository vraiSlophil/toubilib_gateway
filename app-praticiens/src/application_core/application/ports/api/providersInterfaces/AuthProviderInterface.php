<?php
namespace toubilib\core\application\ports\api\providersInterfaces;


use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\api\dtos\outputs\AuthDTO;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;

interface AuthProviderInterface {
    public function register(CredentialsDTO $credentials, int $role): ProfileDTO;
    public function signin(CredentialsDTO $credentials): AuthDTO;
    public function getSignedInUser(string $token): ProfileDTO;
}