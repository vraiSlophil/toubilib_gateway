<?php
// toubilib/src/application_core/application/ports/spi/adapterInterface/OutputFormatterInterface.php
namespace toubilib\core\application\ports\spi\adapterInterface;

use Psr\Http\Message\ResponseInterface;
use Throwable;

interface ApiResponseBuilderInterface
{
    public static function create(bool $debug = false): self;
    public function status(int $code): self;
    public function data(mixed $data): self;
    public function addLink(string $rel, array $link): self;
    public function links(array $links): self;
    public function header(string $name, string $value): self;
    public function error(string $publicMessage, ?Throwable $e = null): self;
    public function build(ResponseInterface $response): ResponseInterface;
}