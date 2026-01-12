<?php

namespace toubilib\api\providers\auth;

use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\api\dtos\outputs\AuthDTO;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\application\ports\api\providersInterfaces\JwtManagerInterface;
use toubilib\core\application\ports\api\servicesInterfaces\AuthnServiceInterface;
use toubilib\core\domain\exceptions\AuthenticationFailedException;
use toubilib\core\domain\exceptions\AuthProviderInvalidAccessToken;
use toubilib\core\domain\exceptions\AuthProviderInvalidCredentials;
use toubilib\core\domain\exceptions\JwtManagerExpiredTokenException;
use toubilib\core\domain\exceptions\JwtManagerInvalidTokenException;

class JwtAuthProvider implements AuthProviderInterface
{
    private AuthnServiceInterface $authnService;
    private JwtManagerInterface $jwtManager;

    public function __construct(AuthnServiceInterface $authnService, JwtManagerInterface $jwtManager)
    {
        $this->authnService = $authnService;
        $this->jwtManager = $jwtManager;
    }

    public function signin(CredentialsDTO $credentials): AuthDTO
    {
        try {
            $profile = $this->authnService->authenticate($credentials);
            $access_token = $this->jwtManager->create([
                'id' => $profile->ID,
                'email' => $profile->email,
                'role' => $profile->role
            ], JwtManagerInterface::ACCESS_TOKEN);
            $refresh_token = $this->jwtManager->create([
                'id' => $profile->ID,
                'email' => $profile->email,
                'role' => $profile->role
            ], JwtManagerInterface::REFRESH_TOKEN);
            $authDTO = new AuthDTO($profile, $access_token, $refresh_token);
        } catch (AuthenticationFailedException $e) {
            throw new AuthProviderInvalidCredentials('Invalid credentials');
        }
        return $authDTO;
    }

    public function register(CredentialsDTO $credentials, int $role): ProfileDTO
    {
        return $this->authnService->register($credentials, $role);
    }

    public function getSignedInUser(string $token): ProfileDTO
    {
        try {
            $payload = $this->jwtManager->validate($token);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new AuthenticationFailedException('expired access token :' . $e->getMessage());
        } catch (JwtManagerInvalidTokenException $e) {
            throw new AuthProviderInvalidAccessToken('invalid access token :' . $e->getMessage());
        }
        return new ProfileDTO($payload['id'], $payload['email'], $payload['role']);
    }
}