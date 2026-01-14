<?php

namespace toubilib\api\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use toubilib\api\providers\auth\JwtPayloadDecoder;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\usecases\AuthzService;
use toubilib\infra\adapters\ApiResponseBuilder;

final class AuthzMiddleware
{
    private JwtPayloadDecoder $decoder;

    public function __construct(
        private AuthzService $authzService,
        private string       $operation,
        ?JwtPayloadDecoder   $decoder = null
    )
    {
        $this->decoder = $decoder ?? new JwtPayloadDecoder();
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $auth = $request->getAttribute('authenticated_user');

        if (!$auth) {
            $authorization = $request->getHeaderLine('Authorization');
            if ($authorization === '') {
                return $this->unauthorized('Missing Authorization header');
            }

            if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
                return $this->unauthorized('Invalid Authorization header');
            }

            $token = trim($matches[1]);
            if ($token === '') {
                return $this->unauthorized('Invalid Authorization header');
            }

            $auth = $this->profileFromToken($token);
            if ($auth === null) {
                return $this->unauthorized('Invalid JWT token');
            }

            $request = $request->withAttribute('authenticated_user', $auth);
        }

        $route = RouteContext::fromRequest($request)->getRoute();
        $routeArgs = $route ? $route->getArguments() : [];
        $praticienId = $request->getAttribute('praticienId') ?? ($routeArgs['praticienId'] ?? '');
        $rdvId = $request->getAttribute('rdvId') ?? ($routeArgs['rdvId'] ?? '');


        $authorized = match ($this->operation) {
            'viewAgenda' => $this->authzService->canAccessPraticienAgenda($auth, $praticienId),
            'viewRdv' => $this->authzService->canAccessRdvDetails($auth, $rdvId),
            'cancelRdv' => $this->authzService->canCancelRdv($auth, $rdvId),
            'editRdv' => $this->authzService->canEditRdv($auth, $rdvId),
            'createRdv' => $this->authzService->canCreateRdv($auth),
            'listRdvs' => $this->authzService->canListUserRdvs($auth),
            'manageIndisponibilites' => $this->authzService->canManageIndisponibilites($auth, $praticienId),
            default => false
        };


        if (!$authorized) {
            return ApiResponseBuilder::create()
                ->status(403)
                ->error('Forbidden: insufficient permissions')
                ->build(new Response());
        }

        return $handler->handle($request);
    }

    private function profileFromToken(string $token): ?ProfileDTO
    {
        $payload = $this->decoder->decode($token);
        if (!$payload) {
            return null;
        }

        $upr = $payload['upr'] ?? null;
        if (!is_array($upr)) {
            return null;
        }

        $id = $upr['id'] ?? null;
        $email = $upr['email'] ?? null;
        $role = $upr['role'] ?? null;
        if ($id === null || $email === null || $role === null) {
            return null;
        }

        return new ProfileDTO((string) $id, (string) $email, (int) $role);
    }

    private function unauthorized(string $message): Response
    {
        return ApiResponseBuilder::create()
            ->status(401)
            ->error($message)
            ->build(new Response());
    }
}
