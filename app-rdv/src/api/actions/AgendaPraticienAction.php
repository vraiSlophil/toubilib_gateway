<?php

declare(strict_types=1);

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class AgendaPraticienAction
{
    public function __construct(private ServiceRdvInterface $serviceRdv) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? '';
        $params = $request->getQueryParams();

        if ($praticienId === '' || empty($params['debut']) || empty($params['fin'])) {
            return ApiResponseBuilder::create()->status(400)->error('Missing period or praticienId')->build($response);
        }

        try {
            $debut = new DateTimeImmutable($params['debut']);
            $fin = new DateTimeImmutable($params['fin']);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid date range', $e)->build($response);
        }

        if ($debut > $fin) {
            return ApiResponseBuilder::create()->status(400)->error('Invalid date range')->build($response);
        }

        $slots = $this->serviceRdv->listCreneauxPris($praticienId, $debut, $fin);
        $data = array_map(static function ($dto) {
            $item = $dto->jsonSerialize();
            $item['_links'] = [
                'rdv' => ['href' => '/api/rdvs/' . $item['rdvId']],
                'patient' => ['href' => '/api/patients/' . $item['patientId']],
            ];
            return $item;
        }, $slots);

        $links = [
            'self' => ['href' => '/api/praticiens/' . $praticienId . '/rdvs?debut=' . urlencode($params['debut']) . '&fin=' . urlencode($params['fin'])],
            'praticien' => ['href' => '/api/praticiens/' . $praticienId],
        ];

        return ApiResponseBuilder::create()->status(200)->data($data)->links($links)->build($response);
    }
}

