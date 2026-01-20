<?php
declare(strict_types=1);

namespace toubilib\gateway\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

final class UuidParamMiddleware implements MiddlewareInterface
{
    /**
     * @param string[] $paramNames
     */
    public function __construct(private array $paramNames)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = RouteContext::fromRequest($request)->getRoute();
        if ($route === null) {
            return $handler->handle($request);
        }

        $args = $route->getArguments();
        foreach ($this->paramNames as $paramName) {
            if (!array_key_exists($paramName, $args)) {
                continue;
            }

            $value = (string) $args[$paramName];
            if ($value === '' || !$this->isValidUuid($value)) {
                return $this->badRequest($paramName);
            }
        }

        return $handler->handle($request);
    }

    private function isValidUuid(string $value): bool
    {
        return (bool) preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/',
            $value
        );
    }

    private function badRequest(string $paramName): ResponseInterface
    {
        $response = new Response();
        $payload = json_encode(
            ['error' => ['message' => 'Invalid UUID for ' . $paramName]],
            JSON_UNESCAPED_SLASHES
        );
        $response->getBody()->write($payload === false ? 'null' : $payload);
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
}
