<?php
declare(strict_types=1);

namespace toubilib\gateway\Action;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
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
    public function __construct(private Client $client)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        $path = ltrim($request->getUri()->getPath(), '/'); // ex: api/praticiens/123
        $upstreamPath = preg_replace('#^api/#', '', $path) ?? $path; // ex: praticiens/123

        if ($method === 'OPTIONS') {
            return $this->json($response->withStatus(204), null);
        }

        $bodyStream = $request->getBody();
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        $apiResponse = $this->client->request($method, $upstreamPath, [
            'headers' => $this->forwardHeaders($request),
            'body'    => (string) $bodyStream,
            'query'   => $request->getQueryParams(),
        ]);

        $status = $apiResponse->getStatusCode();
        $apiBody = (string) $apiResponse->getBody();

        if ($status === 404) {
            return $this->json($response->withStatus(404), [
                'error' => ['message' => 'Praticien not found'],
            ]);
        }

        if ($status >= 500 && $this->looksLikeInvalidUuidError($apiBody)) {
            return $this->json($response->withStatus(404), [
                'error' => ['message' => 'Praticien not found'],
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
