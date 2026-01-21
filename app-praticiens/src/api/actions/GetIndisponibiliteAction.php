<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetIndisponibiliteAction
{
    public function __construct(
        private ServiceIndisponibilite $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? '';
        $indispoId = $args['indispoId'] ?? '';

        if ($praticienId === '' || $indispoId === '') {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing praticienId or indispoId')
                ->build($response);
        }

        $indispo = $this->serviceIndisponibilite->getById($indispoId);
        if ($indispo === null) {
            return ApiResponseBuilder::create()
                ->status(404)
                ->error('Indisponibilite not found')
                ->build($response);
        }

        if ($indispo->praticienId !== $praticienId) {
            return ApiResponseBuilder::create()
                ->status(403)
                ->error('Forbidden')
                ->build($response);
        }

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($indispo)
            ->build($response);
    }
}
