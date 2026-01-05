<?php
declare(strict_types=1);

namespace toubilib\gateway\Action;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class GetPraticiensAction
{
    public function __construct(private Client $client)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // L'API toubilib expose la liste sur /api/praticiens
        $apiResponse = $this->client->get('/api/praticiens');

        $response->getBody()->write((string) $apiResponse->getBody());

        return $response
            ->withStatus($apiResponse->getStatusCode())
            ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type') ?: 'application/json');
    }
}