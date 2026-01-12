<?php
declare(strict_types=1);

namespace toubilib\gateway\Action;

use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Response;

/**
 * Action générique qui propage méthode, URI et corps vers l'API toubilib.
 * Supposé que la gateway expose les mêmes chemins que l'API cible.
 */
final class ProxyAction
{
    private Client $client;
    private Client $praticiensClient;
    private Client $rdvClient;

    public function __construct(ContainerInterface $container)
    {
        $this->client = $container->get('client.api');
        $this->praticiensClient = $container->get('client.praticiens');
        $this->rdvClient = $container->get('client.rdv');
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        $path = ltrim($request->getUri()->getPath(), '/');

        // Choix du service en fonction du chemin (ex. 3 + ex. 4)
        if (str_starts_with($path, 'api/praticiens')) {
            $targetClient = $this->praticiensClient;
            $isPraticiensRoute = true;
        } elseif (str_starts_with($path, 'api/rdvs')) {
            $targetClient = $this->rdvClient;
            $isPraticiensRoute = false;
        } else {
            $targetClient = $this->client;
            $isPraticiensRoute = false;
        }

        $upstreamPath = preg_replace('#^api/#', '', $path) ?? $path;

        if ($method === 'OPTIONS') {
            return $this->json($response->withStatus(204), null);
        }

        $bodyStream = $request->getBody();
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        $apiResponse = $targetClient->request($method, $upstreamPath, [
            'headers' => $this->forwardHeaders($request),
            'body'    => (string) $bodyStream,
            'query'   => $request->getQueryParams(),
        ]);

        $status = $apiResponse->getStatusCode();
        $apiBody = (string) $apiResponse->getBody();

        if ($status === 404) {
            return $this->json($response->withStatus(404), [
                'error' => ['message' => 'Resource not found'],
            ]);
        }

        // Cas "UUID invalide" => uniquement pour les routes praticiens
        if ($isPraticiensRoute && $status >= 500 && $this->looksLikeInvalidUuidError($apiBody)) {
            return $this->json($response->withStatus(404), [
                'error' => ['message' => 'Resource not found'],
            ]);
        }

        $decoded = json_decode($apiBody, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->jsonRaw($response->withStatus($status), $apiBody);
        }

        return $this->json($response->withStatus($status), [
            'data' => $apiBody,
        ]);
    }

    /** @param mixed $data */
    private function json(ResponseInterface $response, $data): ResponseInterface
    {
        $payload = json_encode($data, JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload === false ? 'null' : $payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function jsonRaw(ResponseInterface $response, string $rawJson): ResponseInterface
    {
        $response->getBody()->write($rawJson);
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function looksLikeInvalidUuidError(?string $body): bool
    {
        if ($body === null || $body === '') {
            return false;
        }

        return (bool) preg_match('/(invalid input syntax for type uuid|SQLSTATE\[22P02])/i', $body);
    }

    /**
     * Filtre simple des headers à forward (sans Host ni Content-Length).
     * Ajustable au besoin (Auth, etc.).
     */
    private function forwardHeaders(ServerRequestInterface $request): array
    {
        $headers = $request->getHeaders();
        unset($headers['Host'], $headers['Content-Length']);

        unset($headers['Origin']);

        return $headers;
    }
}
