<?php
declare(strict_types=1);

namespace toubilib\gateway\Action;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

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
        $method = $request->getMethod();

        $path = ltrim($request->getUri()->getPath(), '/'); // ex: api/praticiens/123

        $bodyStream = $request->getBody();
        if ($bodyStream->isSeekable()) {
            $bodyStream->rewind();
        }

        try {
            $apiResponse = $this->client->request($method, $path, [
                'headers' => $this->forwardHeaders($request),
                'body'    => (string) $bodyStream,
                'query'   => $request->getQueryParams(),
            ]);
        } catch (RequestException $e) {
            $status = $e->getResponse()?->getStatusCode();
            if ($status === 404) {
                throw new HttpNotFoundException($request, 'Resource not found');
            }

            $status = $status ?? 502;
            $apiResponse = $e->getResponse();
            $response = $response->withStatus($status);

            if ($apiResponse) {
                $response->getBody()->write((string) $apiResponse->getBody());
                $contentType = $apiResponse->getHeaderLine('Content-Type') ?: 'application/json';
                return $response->withHeader('Content-Type', $contentType);
            }

            $response->getBody()->write(json_encode(['error' => 'Upstream error'], JSON_UNESCAPED_SLASHES));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write((string) $apiResponse->getBody());
        $contentType = $apiResponse->getHeaderLine('Content-Type') ?: 'application/json';
        return $response
            ->withStatus($apiResponse->getStatusCode())
            ->withHeader('Content-Type', $contentType);
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
