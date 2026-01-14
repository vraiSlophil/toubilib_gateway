<?php

namespace toubilib\api\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use toubilib\infra\adapters\ApiResponseBuilder;

final class CorsMiddleware implements MiddlewareInterface
{
    /** @var array<string,mixed> */
    private array $options;

    /**
     * @param array{
     *   allowed_origins?: string[],
     *   allowed_methods?: string[],
     *   allowed_headers?: string[],
     *   exposed_headers?: string[],
     *   allow_credentials?: bool,
     *   max_age?: int
     * } $options
     */
    public function __construct(array $options = [], private bool $debug = false)
    {
        $this->options = array_merge([
            'allowed_origins'   => ['*'],
            'allowed_methods'   => ['GET','POST','PUT','PATCH','DELETE','OPTIONS'],
            'allowed_headers'   => ['Content-Type','Authorization','Accept','Origin'],
            'exposed_headers'   => ['Location'],
            'allow_credentials' => true,
            'max_age'           => 86400,
        ], $options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin        = $request->getHeaderLine('Origin');
        $originAllowed = $this->isOriginAllowed($origin);
        $method        = strtoupper($request->getMethod());

        // Préflight
        if ($method === 'OPTIONS') {
            $resp = new Response(204);
            $builder = ApiResponseBuilder::create($this->debug);
            $resp = $builder->status(204)->data(null)->build($resp);
            return $this->withCorsHeaders($resp, $origin, $originAllowed, true);
        }

        // Origine refusée -> 403 JSON (sans en-têtes CORS permissifs)
        if ($origin !== '' && !$originAllowed) {
            $resp = new Response(403);
            $builder = ApiResponseBuilder::create($this->debug);
            $resp = $builder->status(403)->error('Origin not allowed')->build($resp);
            return $resp->withHeader('Vary', 'Origin');
        }

        // Flux normal
        $response = $handler->handle($request);
        return $this->withCorsHeaders($response, $origin, $originAllowed, false);
    }

    private function isOriginAllowed(string $origin): bool
    {
        if ($origin === '') return false;
        $allowed = $this->options['allowed_origins'];
        if (in_array('*', $allowed, true)) return true;
        return in_array($origin, $allowed, true);
    }

    private function withCorsHeaders(ResponseInterface $response, string $origin, bool $originAllowed, bool $isPreflight): ResponseInterface
    {
        $response = $response->withHeader('Vary', 'Origin');

        if ($originAllowed && $origin !== '') {
            $response = $response->withHeader(
                'Access-Control-Allow-Origin',
                in_array('*', $this->options['allowed_origins'], true) ? '*' : $origin
            );
            if ($this->options['allow_credentials'] && !in_array('*', $this->options['allowed_origins'], true)) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        if ($isPreflight) {
            return $response
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->options['allowed_methods']))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->options['allowed_headers']))
                ->withHeader('Access-Control-Max-Age', (string)$this->options['max_age']);
        }

        if (!empty($this->options['exposed_headers'])) {
            $response = $response->withHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->options['exposed_headers'])
            );
        }

        return $response;
    }
}