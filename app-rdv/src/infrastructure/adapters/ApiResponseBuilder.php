<?php

namespace toubilib\infra\adapters;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use toubilib\core\application\ports\spi\adapterInterface\ApiResponseBuilderInterface;

final class ApiResponseBuilder implements ApiResponseBuilderInterface
{
    private ?array $data = null;
    private ?array $error = null;
    private array $links = [];
    private array $headers = ['Content-Type' => 'application/json'];
    private int $status = 200;
    private bool $debug = false;

    public static function create(bool $debug = false): self
    {
        $b = new self();
        $b->debug = $debug;
        return $b;
    }

    public function status(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function data(mixed $data): self
    {
        if (is_array($data)) {
            $this->data = array_map(
                fn($item) => $item instanceof \JsonSerializable ? $item->jsonSerialize() : $item,
                $data
            );
        } elseif ($data instanceof \JsonSerializable) {
            $this->data = $data->jsonSerialize();
        } else {
            $this->data = $data;
        }
        return $this;
    }

    public function addLink(string $rel, array $link): self
    {
        $this->links[$rel] = $link;
        return $this;
    }

    public function links(array $links): self
    {
        foreach ($links as $k => $v) $this->links[$k] = $v;
        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function error(string $publicMessage, ?Throwable $e = null): self
    {
        if ($this->debug && $e) {
            $this->error = [
                'type' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        } else {
            $this->error = ['message' => $publicMessage];
        }
        return $this;
    }

    public function build(ResponseInterface $response): ResponseInterface
    {
        $payload = $this->error
            ? ['error' => $this->error]
            : ['data' => $this->data];

        if ($this->links) {
            $payload['_links'] = $this->links;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($json ?: 'null');
        $response = $response->withStatus($this->status);

        foreach ($this->headers as $k => $v) {
            $lower = strtolower($k);
            if (str_starts_with($lower, 'access-control-') && $response->hasHeader($k)) {
                continue;
            }
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}