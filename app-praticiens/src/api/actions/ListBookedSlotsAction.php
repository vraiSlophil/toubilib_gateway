<?php

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\infra\adapters\ApiResponseBuilder;
use toubilib\infra\adapters\MonologLogger;

final class ListBookedSlotsAction
{
    public function __construct(private ServiceRdvInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? '';
        $q = $request->getQueryParams();

        if (empty($q['debut']) || empty($q['fin'])) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid date range')->build($response);
        }

        try {
            $start = new DateTimeImmutable($q['debut']);
            $end = new DateTimeImmutable($q['fin']);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid date range', $e)->build($response);
        }

        if ($start > $end) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid date range')->build($response);
        }

        $slots = $this->service->listCreneauxPris($praticienId, $start, $end);
        $data = array_map(function ($dto) {
            $item = $dto->jsonSerialize();
            $item['_links'] = [
                'rdv' => ['href' => '/api/rdvs/' . $item['rdvId']],
                'praticien' => ['href' => '/api/praticiens/' . $item['praticienId']]
            ];
            return $item;
        }, $slots);

        $links = [
            'self' => ['href' => '/api/praticiens/' . $praticienId . '/rdvs?debut=' . urlencode($q['debut']) . '&fin=' . urlencode($q['fin'])],
            'praticien' => ['href' => '/api/praticiens/' . $praticienId]
        ];

        return ApiResponseBuilder::create()->status(200)->data($data)->links($links)->build($response);
    }
}