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
            'praticiens' => ['href' => '/api/praticiens']
        ];
        return ApiResponseBuilder::create()
            ->status(200)
            ->data(['message' => 'API root'])
            ->links($links)
            ->build($response);
    }
}
