<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceIndisponibiliteInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetIndisponibiliteAction
{
    public function __construct(
        private ServiceIndisponibiliteInterface $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? '';
        $indispoId = $args['indispoId'] ?? '';

        if ($praticienId === '' || $indispoId === '') {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing praticienId or indispoId')
                ->build($response);
        }

        $indispo = $this->serviceIndisponibilite->getById($indispoId);
        if ($indispo === null) {
            return ApiResponseBuilder::create()
                ->status(404)
                ->error('Indisponibilite not found')
                ->build($response);
        }

        if ($indispo->praticienId !== $praticienId) {
            return ApiResponseBuilder::create()
                ->status(403)
                ->error('Forbidden')
                ->build($response);
        }

        $attributes = $indispo->jsonSerialize();
        $auth = $request->getAttribute('authenticated_user');
        if ($auth !== null && $auth->role !== Roles::PRATICIEN) {
            $attributes['motif'] = null;
        }
        $base = "/api/praticiens/{$praticienId}/indisponibilites/{$indispoId}";
        $links = [
            'self' => ['href' => $base],
            'update' => ['href' => $base, 'meta' => ['method' => 'PUT']],
            'delete' => ['href' => $base, 'meta' => ['method' => 'DELETE']]
        ];

        return ApiResponseBuilder::create()
            ->status(200)
            ->data(ApiResponseBuilder::resourceFromAttributes('indisponibilites', $attributes, 'id', $links))
            ->links(['self' => ['href' => $base]])
            ->build($response);
    }
}
