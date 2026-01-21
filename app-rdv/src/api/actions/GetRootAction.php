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
            'self' => ['href' => '/api'],
            'rdvs' => ['href' => '/api/rdvs']
        ];
        return ApiResponseBuilder::create()
            ->status(200)
            ->data(ApiResponseBuilder::resource('root', 'api', ['message' => 'API root'], ['self' => ['href' => '/api']]))
            ->links($links)
            ->build($response);
    }
}
