<?php

namespace toubilib\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\inputs\InputPatientDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class CreatePatientAction
{
    public function __construct(private ServicePatientInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('authenticated_user');
        if ($user === null) {
            return ApiResponseBuilder::create()->status(401)->error('Unauthorized')->build($response);
        }

        if ($user->role !== Roles::PATIENT) {
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

        $existing = $this->service->getPatientById($user->ID);
        if ($existing !== null) {
            return ApiResponseBuilder::create()
                ->status(409)
                ->error('Patient already exists')
                ->build($response);
        }

        $this->service->createPatient($input, $user->ID, $user->email);
        $patient = $this->service->getPatientById($user->ID);

        $location = '/api/patients/' . $user->ID;
        return ApiResponseBuilder::create()
            ->status(201)
            ->data($patient !== null
                ? ApiResponseBuilder::resourceFromAttributes(
                    'patients',
                    $patient->jsonSerialize(),
                    'id',
                    ['self' => ['href' => $location]]
                )
                : ApiResponseBuilder::resource('patients', $user->ID, [], ['self' => ['href' => $location]])
            )
            ->links(['self' => ['href' => $location]])
            ->header('Location', $location)
            ->build($response);
    }
}
