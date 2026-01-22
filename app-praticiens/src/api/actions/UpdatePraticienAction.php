<?php

namespace toubilib\api\actions;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\inputs\InputPraticienDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\domain\exceptions\PraticienNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class UpdatePraticienAction
{
    public function __construct(private ServicePraticienInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['praticienId'] ?? '';
        if ($id === '') {
            return ApiResponseBuilder::create()->status(400)->error('Missing praticienId parameter')->build($response);
        }

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

        try {
            $detail = $this->service->updatePraticien($id, $input);
        } catch (PraticienNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Praticien not found', $e)->build($response);
        }

        $attributes = $detail->jsonSerialize();
        $location = '/api/praticiens/' . $id;
        $resource = ApiResponseBuilder::resourceFromAttributes(
            'praticiens',
            $attributes,
            'id',
            ['self' => ['href' => $location]]
        );

        return ApiResponseBuilder::create()
            ->status(200)
            ->data($resource)
            ->links(['self' => ['href' => $location]])
            ->build($response);
    }
}
