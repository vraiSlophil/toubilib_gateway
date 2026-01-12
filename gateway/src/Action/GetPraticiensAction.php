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
        $apiResponse = $this->client->get('praticiens');

        $response->getBody()->write((string) $apiResponse->getBody());

        return $response
            ->withStatus($apiResponse->getStatusCode())
            ->withHeader('Content-Type', $apiResponse->getHeaderLine('Content-Type') ?: 'application/json');
    }
}