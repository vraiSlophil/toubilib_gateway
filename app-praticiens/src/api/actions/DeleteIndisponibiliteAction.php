<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\core\domain\exceptions\IndisponibiliteNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class DeleteIndisponibiliteAction
{
    public function __construct(
        private ServiceIndisponibilite $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'];
        $indispoId = $args['indispoId'];

        try {
            // Verify the indisponibilite belongs to this praticien
            $indispo = $this->serviceIndisponibilite->getById($indispoId);
            if ($indispo === null) {
                return ApiResponseBuilder::create()
                    ->status(404)
                    ->error('Indisponibilite not found')
                    ->build(new Response());
            }

            if ($indispo->praticienId !== $praticienId) {
                return ApiResponseBuilder::create()
                    ->status(403)
                    ->error('Forbidden')
                    ->build(new Response());
            }

            $this->serviceIndisponibilite->supprimerIndisponibilite($indispoId);

            return $response->withStatus(204);
        } catch (IndisponibiliteNotFoundException $e) {
            return ApiResponseBuilder::create()
                ->status(404)
                ->error($e->getMessage())
                ->build(new Response());
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error($e->getMessage())
                ->build(new Response());
        }
    }
}

