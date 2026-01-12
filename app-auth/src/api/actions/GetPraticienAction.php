<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetPraticienAction
{
    public function __construct(private ServicePraticienInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['praticienId'] ?? '';
        $dto = $this->service->getPraticienDetail($id);
        if (!$dto) {
            return ApiResponseBuilder::create()->status(404)->error('Praticien not found')->build($response);
        }

        $data = $dto->jsonSerialize();
        $links = [
            'self' => ['href' => '/api/praticiens/' . $id],
            'rdvs' => [
                'href' => '/api/praticiens/' . $id . '/rdvs{?debut,fin}',
                'templated' => true
            ]
        ];

        return ApiResponseBuilder::create()->status(200)->data($data)->links($links)->build($response);
    }
}