<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\infra\adapters\ApiResponseBuilder;

final class ListPraticiensAction
{
    public function __construct(private ServicePraticienInterface $service) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $specialiteId = null;
        if (array_key_exists('specialiteId', $params) && $params['specialiteId'] !== '') {
            $specialiteId = filter_var($params['specialiteId'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($specialiteId === false) {
                return ApiResponseBuilder::create()->status(400)->error('specialiteId invalide')->build($response);
            }
        }

        $ville = array_key_exists('ville', $params) ? trim((string)$params['ville']) : null;
        if ($ville === '') {
            $ville = null;
        }

        try {
            $praticiens = ($specialiteId !== null || $ville !== null)
                ? $this->service->rechercherPraticiens($specialiteId, $ville)
                : $this->service->listerPraticiens();

            $items = array_map(static function ($dto) {
                $data = $dto->jsonSerialize();
                $data['_links'] = [
                    'self' => ['href' => '/api/praticiens/' . $data['id']],
                    'rdvs' => [
                        'href' => '/api/praticiens/' . $data['id'] . '/rdvs{?debut,fin}',
                        'templated' => true,
                    ],
                ];
                return $data;
            }, $praticiens);

            return ApiResponseBuilder::create()
                ->status(200)
                ->data($items)
                ->links(['self' => ['href' => '/api/praticiens{?specialiteId,ville}']])
                ->build($response);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error('Failed to list praticiens', $e)
                ->build($response);
        }
    }
}
