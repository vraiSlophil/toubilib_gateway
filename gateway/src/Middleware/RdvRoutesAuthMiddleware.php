<?php

declare(strict_types=1);

namespace toubilib\gateway\Middleware;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;

/**
 * TD2.2 - Ex 4
 * Middleware d'authentification (gateway) appliqué au bloc /api/rdvs.
 *
 * Il NE fait le contrôle que pour:
 * - POST /api/rdvs
 * - GET  /api/rdvs/{rdvId}
 *
 * Le contrôle consiste à appeler le microservice auth:
 * POST /api/tokens/validate (via client.auth configuré avec base_uri .../api/)
 */
final class RdvRoutesAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Client $authClient)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $needsAuth = (
            ($method === 'POST' && $path === '/api/rdvs')
            || ($method === 'GET' && (bool) preg_match('#^/api/rdvs/[^/]+$#', $path))
        );

        if (!$needsAuth) {
            return $handler->handle($request);
        }

        $token = $this->extractBearerToken($request);

        $res = $this->authClient->request('POST', 'tokens/validate', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);

        if ($res->getStatusCode() !== 200) {
            throw new HttpUnauthorizedException($request, 'Invalid JWT token');
        }

        return $handler->handle($request);
    }

    private function extractBearerToken(ServerRequestInterface $request): string
    {
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization === '') {
            throw new HttpUnauthorizedException($request, 'Missing Authorization header');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
        }

        $token = trim($matches[1]);
        if ($token === '') {
            throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
        }

        return $token;
    }
}
