<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class GetPatientAction
{
    public function __construct(private ServicePatientInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $user = $request->getAttribute('authenticated_user');
        if ($user === null) {
            return ApiResponseBuilder::create()->status(401)->error('Unauthorized')->build($response);
        }

        $patientId = $args['patientId'] ?? '';
        if ($patientId === '') {
            return ApiResponseBuilder::create()->status(400)->error('Missing patientId parameter')->build($response);
        }

        if ($user->role === Roles::PATIENT && $user->ID !== $patientId) {
            return ApiResponseBuilder::create()->status(403)->error('Forbidden: insufficient permissions')->build($response);
        }

        $patient = $this->service->getPatientById($patientId);
        if ($patient === null) {
            return ApiResponseBuilder::create()->status(404)->error('Patient not found')->build($response);
        }

        $attributes = $patient->jsonSerialize();
        $links = [
            'self' => ['href' => '/api/patients/' . $patientId],
        ];

        return ApiResponseBuilder::create()
            ->status(200)
            ->data(ApiResponseBuilder::resourceFromAttributes('patients', $attributes, 'id', $links))
            ->links(['self' => ['href' => '/api/patients/' . $patientId]])
            ->build($response);
    }
}
