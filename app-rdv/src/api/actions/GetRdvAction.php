<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetRdvAction
{
    public function __construct(private ServiceRdvInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['rdvId'] ?? '';
        $dto = $this->service->getRdvById($id);
        if (!$dto) {
            return ApiResponseBuilder::create()->status(404)->error('Rdv not found')->build($response);
        }

        $attributes = $dto->jsonSerialize();
        $praticienId = (string)($attributes['praticienId'] ?? '');
        $links = [
            'self' => ['href' => '/api/rdvs/' . $id],
            'praticien' => ['href' => '/api/praticiens/' . $praticienId],
            'cancel' => ['href' => '/api/rdvs/' . $id, 'meta' => ['method' => 'DELETE']]
        ];
        $resource = ApiResponseBuilder::resourceFromAttributes('rdvs', $attributes, 'id', $links);

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($resource)
            ->links(['self' => ['href' => '/api/rdvs/' . $id]])
            ->build($response);
    }
}
