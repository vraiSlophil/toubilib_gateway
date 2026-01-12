<?php

namespace toubilib\api\actions\auth;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpBadRequestException;
use toubilib\core\application\ports\api\dtos\inputs\CredentialsDTO;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\domain\exceptions\AuthenticationFailedException;
use toubilib\core\domain\exceptions\DuplicateEmailException;
use toubilib\core\domain\exceptions\InvalidPasswordException;
use toubilib\infra\adapters\ApiResponseBuilder;

class SignupAction
{
    private AuthProviderInterface $authProvider;

    public function __construct(AuthProviderInterface $authProvider)
    {
        $this->authProvider = $authProvider;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (empty($data['email']) || empty($data['password'])) {
            return ApiResponseBuilder::create()->status(400)->error('Missing email or password', (throw new HttpBadRequestException($request, 'Missing email or password')))->build($response);
        }

        // Par défaut, les nouveaux comptes sont des patients (rôle 1)
        $role = $data['role'] ?? 1;

        // Optionnel : restreindre la création de praticiens
        if ($role === 10) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid role', (throw new HttpBadRequestException($request, 'Cannot register as practitioner')))->build($response);
        }

        $credentials = new CredentialsDTO($data['email'], $data['password']);

        try {
            $profile = $this->authProvider->register($credentials, $role);
        } catch (InvalidPasswordException $e) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid password', $e)->build($response);
        } catch (DuplicateEmailException $e) {
            return ApiResponseBuilder::create()->status(400)->error('Email already exists', $e)->build($response);
        } catch (Exception $e) {
            throw new HttpBadRequestException($request, $e->getMessage());
        }

        return ApiResponseBuilder::create()
            ->status(201)
            ->data([
                'id' => $profile->ID,
                'email' => $profile->email,
                'role' => $profile->role
            ])
            ->build($response);
    }
}