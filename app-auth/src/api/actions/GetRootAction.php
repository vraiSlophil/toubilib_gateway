<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetRootAction
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $links = [
            'signup' => ['href' => '/api/auth/signup', 'method' => 'POST'],
            'signin' => ['href' => '/api/auth/signin', 'method' => 'POST'],
            'refresh' => ['href' => '/api/auth/refresh', 'method' => 'POST'],
            'validate' => ['href' => '/api/tokens/validate', 'method' => 'POST']
        ];
        return ApiResponseBuilder::create()
            ->status(200)
            ->data(['message' => 'API root'])
            ->links($links)
            ->build($response);
    }
}
