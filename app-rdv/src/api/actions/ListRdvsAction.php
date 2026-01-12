<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\ports\api\dtos\outputs\RendezVousDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class ListRdvsAction
{
    public function __construct(private ServiceRdvInterface $serviceRdv)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var ProfileDTO|null $user */
        $user = $request->getAttribute('authenticated_user');
        if ($user === null) {
            return ApiResponseBuilder::create()
                ->status(401)
                ->error('Unauthorized')
                ->build($response);
        }

        $queryParams = $request->getQueryParams();

        $debut = isset($queryParams['debut']) ? new DateTimeImmutable($queryParams['debut']) : null;
        $fin = isset($queryParams['fin']) ? new DateTimeImmutable($queryParams['fin']) : null;
        $praticienId = $queryParams['praticienId'] ?? null;
        $pastOnly = isset($queryParams['history']) || str_ends_with($request->getUri()->getPath(), '/history');

//        echo json_encode(['debut' => $debut, 'fin' => $fin, 'praticienId' => $praticienId, 'history' => $pastOnly]);

        $rdvs = $this->serviceRdv->listRdvsFiltered($user, $debut, $fin, $praticienId, $pastOnly);

        $data = array_map(fn(RendezVousDTO $dto) => $dto->jsonSerialize() + [
                '_links' => [
                    'self' => ['href' => '/api/rdvs/' . $dto->id],
                    'cancel' => ['href' => '/api/rdvs/' . $dto->id, 'method' => 'DELETE']
                ]
            ], $rdvs);

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($data)
            ->links(['self' => ['href' => $request->getUri()->getPath()]])
            ->build($response);
    }
}
