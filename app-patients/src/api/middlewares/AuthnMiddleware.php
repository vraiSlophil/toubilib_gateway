<?php

namespace toubilib\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use toubilib\api\providers\auth\JwtPayloadDecoder;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\infra\adapters\ApiResponseBuilder;

class AuthnMiddleware
{
    private JwtPayloadDecoder $decoder;

    public function __construct(?JwtPayloadDecoder $decoder = null)
    {
        $this->decoder = $decoder ?? new JwtPayloadDecoder();
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeaderLine('Authorization');
        if ($authorization === '') {
            return $this->unauthorized('Missing Authorization header');
        }

        if (!preg_match('/^Bearer\\s+(.+)$/i', $authorization, $matches)) {
            return $this->unauthorized('Invalid Authorization header');
        }
        $token = trim($matches[1]);
        if ($token === '') {
            return $this->unauthorized('Invalid Authorization header');
        }

        $authDto = $this->profileFromToken($token);
        if ($authDto === null) {
            return $this->unauthorized('Invalid JWT token');
        }

        return $handler->handle($request->withAttribute('authenticated_user', $authDto));
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
