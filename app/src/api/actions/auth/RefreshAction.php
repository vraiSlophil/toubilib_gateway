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
 * Refresh d'un couple (access, refresh) JWT.
 *
 * Contrat attendu (simple, pour le TD):
 * - input JSON: {"refreshToken": "..."}
 * - output 200: { data: { accessToken, refreshToken } }
 * - 400: body invalide / refreshToken manquant
 * - 401: refresh token invalide / expir
 */
final class RefreshAction
{
    public function __construct(private JwtManagerInterface $jwtManager)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $refreshToken = is_array($data) ? ($data['refreshToken'] ?? '') : '';

        if (!is_string($refreshToken) || trim($refreshToken) === '') {
            throw new HttpBadRequestException($request, 'Missing refreshToken');
        }

        try {
            // NB: JwtManager::validate() retourne (array) $jwtToken->upr
            // donc directement: ['id' => ..., 'email' => ..., 'role' => ...]
            $upr = $this->jwtManager->validate($refreshToken);
        } catch (JwtManagerExpiredTokenException $e) {
            throw new HttpUnauthorizedException($request, 'Expired refresh token');
        } catch (JwtManagerInvalidTokenException $e) {
            throw new HttpUnauthorizedException($request, 'Invalid refresh token');
        }

        if (!is_array($upr) || !isset($upr['id'], $upr['email'], $upr['role'])) {
            throw new HttpUnauthorizedException($request, 'Invalid refresh token payload');
        }

        $newAccess = $this->jwtManager->create([
            'id' => (string) $upr['id'],
            'email' => (string) $upr['email'],
            'role' => (int) $upr['role'],
        ], JwtManagerInterface::ACCESS_TOKEN);

        $newRefresh = $this->jwtManager->create([
            'id' => (string) $upr['id'],
            'email' => (string) $upr['email'],
            'role' => (int) $upr['role'],
        ], JwtManagerInterface::REFRESH_TOKEN);

        return ApiResponseBuilder::create()
            ->status(200)
            ->data([
                'accessToken' => $newAccess,
                'refreshToken' => $newRefresh,
            ])
            ->build($response);
    }
}
