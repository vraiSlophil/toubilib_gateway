<?php

declare(strict_types=1);

namespace toubilib\api\actions\auth;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpUnauthorizedException;
use toubilib\core\application\ports\api\providersInterfaces\JwtManagerInterface;
use toubilib\core\domain\exceptions\JwtManagerExpiredTokenException;
use toubilib\core\domain\exceptions\JwtManagerInvalidTokenException;
use toubilib\infra\adapters\ApiResponseBuilder;

/**
 * TD2.2 - Ex 3
 * Valide un access token JWT.
 *
 * Entrées acceptées:
 * - header Authorization: Bearer <token>
 * - ou body JSON: {"token": "..."}
 *
 * Sorties:
 * - 200 si le token est valide
 * - 401 si invalide/expiré, avec un message explicite
 */
final class ValidateTokenAction
{
    public function __construct(private JwtManagerInterface $jwtManager)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $token = $this->extractToken($request);

        try {
            // validate() lève une exception si invalide/expiré
            $upr = $this->jwtManager->validate($token);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new HttpUnauthorizedException($request, 'Token expired');
        } catch (JwtManagerInvalidTokenException $e) {
            throw new HttpUnauthorizedException($request, 'Token invalid');
        }

        // On renvoie le profil décodé: pratique pour les étapes suivantes (gateway middleware)
        return ApiResponseBuilder::create()
            ->status(200)
            ->data([
                'valid' => true,
                'profile' => [
                    'id' => $upr['id'] ?? null,
                    'email' => $upr['email'] ?? null,
                    'role' => $upr['role'] ?? null,
                ],
            ])
            ->build($response);
    }

    private function extractToken(ServerRequestInterface $request): string
    {
        $authorization = $request->getHeaderLine('Authorization');
        if (is_string($authorization) && $authorization !== '') {
            if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
                throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
            }
            $token = trim($matches[1]);
            if ($token === '') {
                throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
            }
            return $token;
        }

        $data = $request->getParsedBody();
        $token = is_array($data) ? ($data['token'] ?? '') : '';
        if (!is_string($token) || trim($token) === '') {
            throw new HttpBadRequestException($request, 'Missing token');
        }
        return trim($token);
    }
}

