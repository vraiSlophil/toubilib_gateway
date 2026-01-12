<?php

namespace toubilib\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use toubilib\core\application\ports\api\dtos\inputs\InputRendezVousDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\domain\entities\Roles;
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
        if ($user === null || $user->role !== Roles::PATIENT) {
            return ApiResponseBuilder::create()
                ->status(403)
                ->error('Only patients can create appointments.')
                ->build($response);
        }

        $payload = array_merge($parsed, [
            'patientId' => $user->ID,
            'patientEmail' => $user->email,
        ]);

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
        } catch (InvalidMotifException|PraticienUnavailableException $e) {
            return ApiResponseBuilder::create()->status(422)->error($e->getMessage(), $e)->build($response);
        } catch (SlotConflictException $e) {
            return ApiResponseBuilder::create()->status(409)->error('Slot conflict', $e)->build($response);
        } catch (Throwable $e) {
            return ApiResponseBuilder::create()->status(500)->error('Internal server error', $e)->build($response);
        }

        $location = '/api/rdvs/' . $rdvId;
        $links = [
            'self' => ['href' => $location],
            'cancel' => ['href' => $location, 'method' => 'DELETE']
        ];

        return ApiResponseBuilder::create()
            ->status(201)
            ->data(['rdv_id' => $rdvId])
            ->links($links)
            ->header('Location', $location)
            ->build($response);
    }
}