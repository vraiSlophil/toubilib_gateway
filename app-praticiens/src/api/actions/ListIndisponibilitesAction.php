<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceIndisponibiliteInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class ListIndisponibilitesAction
{
    public function __construct(
        private ServiceIndisponibiliteInterface $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'];

        try {
            $indisponibilites = $this->serviceIndisponibilite->listForPraticien($praticienId);
            $items = array_map(static function ($dto) use ($praticienId) {
                $attributes = $dto->jsonSerialize();
                $id = (string) ($attributes['id'] ?? '');
                unset($attributes['id']);
                $base = "/api/praticiens/{$praticienId}/indisponibilites/{$id}";
                $links = [
                    'self' => ['href' => $base],
                    'update' => ['href' => $base, 'meta' => ['method' => 'PUT']],
                    'delete' => ['href' => $base, 'meta' => ['method' => 'DELETE']]
                ];
                return ApiResponseBuilder::resource('indisponibilites', $id, $attributes, $links);
            }, $indisponibilites);

            $auth = $request->getAttribute('authenticated_user');
            if ($auth !== null && $auth->role !== Roles::PRATICIEN) {
                $items = array_map(static function (array $resource) {
                    $attributes = $resource['attributes'] ?? null;
                    if (is_array($attributes)) {
                        $attributes['motif'] = null;
                        $resource['attributes'] = $attributes;
                    }
                    return $resource;
                }, $items);
            }

            return ApiResponseBuilder::create()
                ->status(200)
                ->data($items)
                ->links([
                    'self' => [
                        'href' => "/api/praticiens/{$praticienId}/indisponibilites"
                    ],
                    'create' => [
                        'href' => "/api/praticiens/{$praticienId}/indisponibilites",
                        'meta' => ['method' => 'POST']
                    ]
                ])
                ->build($response);
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error($e->getMessage())
                ->build($response);
        }
    }

}
