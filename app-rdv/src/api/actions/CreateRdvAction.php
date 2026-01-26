<?php

namespace toubilib\api\actions;

use DI\NotFoundException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use toubilib\core\application\ports\api\dtos\inputs\InputRendezVousDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\core\domain\exceptions\PatientNotFoundException;
use toubilib\core\domain\exceptions\PraticienNotFoundException;
use toubilib\core\domain\exceptions\InvalidMotifException;
use toubilib\core\domain\exceptions\SlotConflictException;
use toubilib\core\domain\exceptions\PraticienUnavailableException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class CreateRdvAction
{
    public function __construct(private ServiceRdvInterface $serviceRdv)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $parsed = $request->getParsedBody();

        if (!is_array($parsed)) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid request body: expected JSON object.')
                ->build($response);
        }

        $user = $request->getAttribute('authenticated_user');
        if ($user === null) {
            return ApiResponseBuilder::create()
                ->status(401)
                ->error('Missing authenticated user.')
                ->build($response);
        }

        if ($user->role !== Roles::PATIENT && $user->role !== Roles::PRATICIEN) {
            return ApiResponseBuilder::create()
                ->status(403)
                ->error('Only patients or practitioners can create appointments.')
                ->build($response);
        }

        if ($user->role === Roles::PATIENT) {
            $payload = array_merge($parsed, [
                'patientId' => $user->ID,
                'patientEmail' => $user->email,
            ]);
        } else {
            // Practitioners can create an appointment for a patient
            $payload = array_merge($parsed, [
                'praticienId' => $user->ID,
            ]);
        }

        try {
            $input = InputRendezVousDTO::fromArray($payload);
        } catch (InvalidArgumentException $e) {
            return ApiResponseBuilder::create()
                ->status(422)
                ->error('Invalid request body', $e)
                ->build($response);
        }

        $errors = $input->validate();
        if (!empty($errors)) {
            return ApiResponseBuilder::create()
                ->status(422)
                ->error('Validation errors: ' . json_encode($errors))
                ->build($response);
        }

        try {
            $rdvId = $this->serviceRdv->creerRdv($input);
        } catch (PraticienNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Praticien not found', $e)->build($response);
        } catch (PatientNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Patient not found', $e)->build($response);
        } catch (NotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Ressource not found', $e)->build($response);
        } catch (InvalidMotifException|PraticienUnavailableException $e) {
            return ApiResponseBuilder::create()->status(422)->error($e->getMessage(), $e)->build($response);
        } catch (SlotConflictException $e) {
            return ApiResponseBuilder::create()->status(409)->error('Slot conflict', $e)->build($response);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()->status(500)->error('Internal server error', $e)->build($response);
        }

        $location = '/api/rdvs/' . $rdvId;
        $resourceLinks = [
            'self' => ['href' => $location],
            'cancel' => ['href' => $location, 'meta' => ['method' => 'DELETE']]
        ];
        $resource = ApiResponseBuilder::resource('rdvs', $rdvId, [], $resourceLinks);
        $rdv = $this->serviceRdv->getRdvById($rdvId);
        if ($rdv !== null) {
            $attributes = $rdv->jsonSerialize();
            $praticienId = (string)($attributes['praticienId'] ?? '');
            $resourceLinks['praticien'] = ['href' => '/api/praticiens/' . $praticienId];
            $resource = ApiResponseBuilder::resourceFromAttributes('rdvs', $attributes, 'id', $resourceLinks);
        }

        return ApiResponseBuilder::create()
            ->status(201)
            ->data($resource)
            ->links(['self' => ['href' => $location]])
            ->header('Location', $location)
            ->build($response);
    }
}
