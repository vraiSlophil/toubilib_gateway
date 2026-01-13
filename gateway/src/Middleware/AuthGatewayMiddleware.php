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
 * Middleware d'authentification dans la gateway.
 *
 * - vérifie seulement la présence + validité d'un access token JWT
 * - délègue la validation au microservice d'auth: POST /api/tokens/validate
 */
final class AuthGatewayMiddleware implements MiddlewareInterface
{
    public function __construct(private Client $authClient)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
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

        // Appel au microservice auth pour valider le token
        $res = $this->authClient->request('POST', 'tokens/validate', [
            'headers' => [
                // forward du token
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

