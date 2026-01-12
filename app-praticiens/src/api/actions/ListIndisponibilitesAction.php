<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\infra\adapters\ApiResponseBuilder;

final class ListIndisponibilitesAction
{
    public function __construct(
        private ServiceIndisponibilite $serviceIndisponibilite
    )
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'];

        try {
            $indisponibilites = $this->serviceIndisponibilite->listForPraticien($praticienId);

            return ApiResponseBuilder::create()
                ->status(200)
                ->data($indisponibilites)
                ->links([
                    'self' => [
                        'href' => "/api/praticiens/{$praticienId}/indisponibilites"
                    ],
                    'create' => [
                        'href' => "/api/praticiens/{$praticienId}/indisponibilites",
                        'method' => 'POST'
                    ]
                ])
                ->build(new Response());
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error($e->getMessage())
                ->build(new Response());
        }
    }

}

