<?php

namespace toubilib\infra\adapters;

use Psr\Http\Message\ResponseInterface;
use Throwable;
use toubilib\core\application\ports\spi\adapterInterface\ApiResponseBuilderInterface;

final class ApiResponseBuilder implements ApiResponseBuilderInterface
{
    private ?array $data = null;
    private array $errors = [];
    private array $links = [];
    private array $headers = ['Content-Type' => 'application/vnd.api+json'];
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
        $error = ['title' => $publicMessage];
        if ($this->debug && $e) {
            $error['detail'] = $e->getMessage();
            $error['meta'] = [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }
        $this->errors[] = $error;
        return $this;
    }

    public static function resource(
        string $type,
        string $id,
        array $attributes,
        array $links = [],
        array $relationships = []
    ): array {
        $resource = [
            'type' => $type,
            'id' => $id,
            'attributes' => $attributes
        ];
        if ($links) {
            $resource['links'] = $links;
        }
        if ($relationships) {
            $resource['relationships'] = $relationships;
        }
        return $resource;
    }

    public static function resourceFromAttributes(
        string $type,
        array $attributes,
        string $idKey = 'id',
        array $links = [],
        array $relationships = []
    ): array {
        $id = (string)($attributes[$idKey] ?? '');
        unset($attributes[$idKey]);
        return self::resource($type, $id, $attributes, $links, $relationships);
    }

    public function build(ResponseInterface $response): ResponseInterface
    {
        if (in_array($this->status, [204, 304], true)) {
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

        if ($this->errors) {
            $errors = array_map(function (array $error): array {
                if (!isset($error['status'])) {
                    $error['status'] = (string)$this->status;
                }
                return $error;
            }, $this->errors);
            $payload = ['errors' => $errors];
        } else {
            $payload = ['data' => $this->data];
        }

        if ($this->links) {
            $payload['links'] = $this->links;
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
