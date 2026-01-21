<?php

namespace toubilib\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\inputs\InputPatientDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\core\domain\exceptions\PatientNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class UpdatePatientAction
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

        $parsed = $request->getParsedBody();
        if (!is_array($parsed)) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid request body: expected JSON object.')
                ->build($response);
        }

        try {
            $input = InputPatientDTO::fromArray($parsed);
        } catch (InvalidArgumentException $e) {
            return ApiResponseBuilder::create()->status(422)->error($e->getMessage(), $e)->build($response);
        }

        $errors = $input->validate();
        if (!empty($errors)) {
            return ApiResponseBuilder::create()
                ->status(422)
                ->error('Validation errors: ' . json_encode($errors))
                ->build($response);
        }

        try {
            $this->service->updatePatient($patientId, $input, $user->email);
        } catch (PatientNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Patient not found', $e)->build($response);
        }

        $patient = $this->service->getPatientById($patientId);

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($patient !== null
                ? ApiResponseBuilder::resourceFromAttributes(
                    'patients',
                    $patient->jsonSerialize(),
                    'id',
                    ['self' => ['href' => '/api/patients/' . $patientId]]
                )
                : ApiResponseBuilder::resource('patients', $patientId, [], ['self' => ['href' => '/api/patients/' . $patientId]])
            )
            ->links(['self' => ['href' => '/api/patients/' . $patientId]])
            ->build($response);
    }
}
