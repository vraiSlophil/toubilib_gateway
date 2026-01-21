<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
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

        $debut = null;
        $fin = null;
        try {
            if (isset($queryParams['debut'])) {
                $debut = new DateTimeImmutable($queryParams['debut']);
            }
            if (isset($queryParams['fin'])) {
                $fin = new DateTimeImmutable($queryParams['fin']);
            }
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid date range', $e)
                ->build($response);
        }

        if ($debut && $fin && $debut > $fin) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid date range')
                ->build($response);
        }
        $praticienId = $queryParams['praticienId'] ?? null;
        $pastOnly = isset($queryParams['history']) || str_ends_with($request->getUri()->getPath(), '/history');

//        echo json_encode(['debut' => $debut, 'fin' => $fin, 'praticienId' => $praticienId, 'history' => $pastOnly]);

        $rdvs = $this->serviceRdv->listRdvsFiltered($user, $debut, $fin, $praticienId, $pastOnly);

        $data = array_map(static function (RendezVousDTO $dto) {
            $attributes = $dto->jsonSerialize();
            $id = (string)($attributes['id'] ?? '');
            unset($attributes['id']);
            $links = [
                'self' => ['href' => '/api/rdvs/' . $id],
                'cancel' => ['href' => '/api/rdvs/' . $id, 'meta' => ['method' => 'DELETE']]
            ];
            return ApiResponseBuilder::resource('rdvs', $id, $attributes, $links);
        }, $rdvs);

        $self = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();
        if ($query !== '') {
            $self .= '?' . $query;
        }

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($data)
            ->links(['self' => ['href' => $self]])
            ->build($response);
    }
}
