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
 * Applique l'auth uniquement à l'accès agenda praticien:
 * - GET /api/praticiens/{praticienId}/rdvs
 */
final class AgendaPraticienAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Client $authClient)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        $needsAuth = ($method === 'GET' && preg_match('#^/api/praticiens/[^/]+/rdvs$#', $path));
        if (!$needsAuth) {
            return $handler->handle($request);
        }

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
}

