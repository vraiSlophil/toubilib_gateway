<?php

namespace toubilib\api\actions\auth;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpUnauthorizedException;
use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\domain\exceptions\AuthProviderInvalidCredentials;
use toubilib\infra\adapters\ApiResponseBuilder;

class SigninAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();
        $credentials = new CredentialsDTO($data['email'], $data['password']);

        try {
            $auth_dto = $this->authProvider->signin($credentials);
        } catch (AuthProviderInvalidCredentials $e) {
            throw new HttpUnauthorizedException($request, $e->getMessage());
        }

        return ApiResponseBuilder::create()->status(200)->data([
            'profile' => [
                'id' => $auth_dto->profile->ID,
                'email' => $auth_dto->profile->email,
                'role' => $auth_dto->profile->role
            ],
            'accessToken' => $auth_dto->access_token,
            'refreshToken' => $auth_dto->refresh_token
        ])->build($response);
    }
}