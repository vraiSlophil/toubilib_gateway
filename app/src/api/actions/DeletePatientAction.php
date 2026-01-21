<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\core\domain\exceptions\PatientNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class DeletePatientAction
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

        if ($user->role !== Roles::PATIENT || $user->ID !== $patientId) {
            return ApiResponseBuilder::create()->status(403)->error('Forbidden: insufficient permissions')->build($response);
        }

        try {
            $this->service->deletePatient($patientId);
        } catch (PatientNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Patient not found', $e)->build($response);
        }

        return ApiResponseBuilder::create()->status(204)->build($response);
    }
}
