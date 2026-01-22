<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\domain\exceptions\PraticienNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class DeletePraticienAction
{
    public function __construct(private ServicePraticienInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['praticienId'] ?? '';
        if ($id === '') {
            return ApiResponseBuilder::create()->status(400)->error('Missing praticienId parameter')->build($response);
        }

        try {
            $this->service->deletePraticien($id);
        } catch (PraticienNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Praticien not found', $e)->build($response);
        }

        return ApiResponseBuilder::create()->status(204)->build($response);
    }
}
