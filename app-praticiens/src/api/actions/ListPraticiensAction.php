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

        $email = array_key_exists('email', $params) ? trim((string)$params['email']) : null;
        if ($email === '') {
            $email = null;
        }
        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ApiResponseBuilder::create()->status(400)->error('email invalide')->build($response);
        }

        $specialiteId = null;
        $ville = null;
        if ($email === null) {
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
        }

        try {
            if ($email !== null) {
                $praticien = $this->service->findPraticienByEmail($email);
                $praticiens = $praticien ? [$praticien] : [];
            } else {
                $praticiens = ($specialiteId !== null || $ville !== null)
                    ? $this->service->rechercherPraticiens($specialiteId, $ville)
                    : $this->service->listerPraticiens();
            }

            $items = array_map(static function ($dto) {
                $attributes = $dto->jsonSerialize();
                $id = (string)($attributes['id'] ?? '');
                unset($attributes['id']);
                $links = [
                    'self' => ['href' => '/api/praticiens/' . $id],
                    'rdvs' => ['href' => '/api/praticiens/' . $id . '/rdvs'],
                ];
                return ApiResponseBuilder::resource('praticiens', $id, $attributes, $links);
            }, $praticiens);

            $query = [];
            if ($email !== null) {
                $query['email'] = $email;
            } else {
                if ($specialiteId !== null) {
                    $query['specialiteId'] = $specialiteId;
                }
                if ($ville !== null) {
                    $query['ville'] = $ville;
                }
            }
            $self = '/api/praticiens';
            if ($query) {
                $self .= '?' . http_build_query($query);
            }

            return ApiResponseBuilder::create()
                ->status(200)
                ->data($items)
                ->links(['self' => ['href' => $self]])
                ->build($response);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error('Failed to list praticiens', $e)
                ->build($response);
        }
    }
}
