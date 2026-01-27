<?php

namespace toubilib\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\inputs\InputPraticienDTO;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\domain\entities\Roles;
use toubilib\infra\adapters\ApiResponseBuilder;

final class CreatePraticienAction
{
    public function __construct(private ServicePraticienInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsed = $request->getParsedBody();
        if (!is_array($parsed)) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid request body: expected JSON object.')
                ->build($response);
        }

        try {
            $input = InputPraticienDTO::fromArray($parsed);
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

        $auth = $request->getAttribute('authenticated_user');
        $forcedId = null;

        if ($auth instanceof ProfileDTO) {
            if ($auth->role !== Roles::PRATICIEN) {
                return ApiResponseBuilder::create()
                    ->status(403)
                    ->error('Forbidden: insufficient permissions')
                    ->build($response);
            }

            if (strtolower($auth->email) !== strtolower($input->email)) {
                return ApiResponseBuilder::create()
                    ->status(403)
                    ->error('Forbidden: practitioner email must match authenticated user')
                    ->build($response);
            }

            $existing = $this->service->getPraticienDetail($auth->ID);
            if ($existing !== null) {
                return ApiResponseBuilder::create()
                    ->status(409)
                    ->error('Praticien already exists for this user')
                    ->build($response);
            }

            $forcedId = $auth->ID;
        }

        $detail = $this->service->createPraticien($input, $forcedId);
        $attributes = $detail->jsonSerialize();
        $id = (string)($attributes['id'] ?? '');
        $location = '/api/praticiens/' . $id;
        $resource = ApiResponseBuilder::resourceFromAttributes(
            'praticiens',
            $attributes,
            'id',
            ['self' => ['href' => $location]]
        );

        return ApiResponseBuilder::create()
            ->status(201)
            ->data($resource)
            ->links(['self' => ['href' => $location]])
            ->header('Location', $location)
            ->build($response);
    }
}
