<?php

namespace toubilib\api\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use toubilib\core\application\usecases\AuthzService;
use toubilib\infra\adapters\ApiResponseBuilder;

final class AuthzMiddleware
{
    public function __construct(
        private AuthzService $authzService,
        private string       $operation // 'viewAgenda', 'viewRdv', 'cancelRdv', 'createRdv'
    )
    {
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler)
    {
        $auth = $request->getAttribute('authenticated_user');

        if (!$auth) {
            return ApiResponseBuilder::create()
                ->status(401)
                ->error('Unauthorized')
                ->build(new Response());
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
}