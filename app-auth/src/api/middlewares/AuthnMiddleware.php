<?php

namespace toubilib\api\middlewares;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use toubilib\core\application\ports\api\providersInterfaces\AuthProviderInterface;
use toubilib\core\domain\exceptions\AuthProviderExpiredAccessToken;
use toubilib\core\domain\exceptions\AuthProviderInvalidAccessToken;

class AuthnMiddleware
{
    public function __construct(private AuthProviderInterface $authProvider) {}

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
//        echo "'test' : '" .$authorization ."',";
        if ($authorization === '') {
            throw new HttpUnauthorizedException($request, 'Missing Authorization header');
        }

        if (!preg_match('/^Bearer\\s+(.+)$/i', $authorization, $matches)) {
            throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
        }
        $token = trim($matches[1]);
        if ($token === null) {
            throw new HttpUnauthorizedException($request, 'Invalid Authorization header');
        }

        try {
            $authDto = $this->authProvider->getSignedInUser($token);
        } catch (AuthProviderInvalidAccessToken $e) {
            throw new HttpUnauthorizedException($request, 'Invalid JWT token', $e);
        } catch (AuthProviderExpiredAccessToken $e) {
            throw new HttpUnauthorizedException($request, 'Expired JWT token', $e);
        }

        return $handler->handle($request->withAttribute('authenticated_user', $authDto));
    }
}