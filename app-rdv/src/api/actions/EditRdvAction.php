<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\domain\exceptions\RdvNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

class EditRdvAction
{
    public function __construct(
        private ServiceRdvInterface $serviceRdv
    ) {}

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $rdvId = $request->getAttribute('rdvId');

        if (!$rdvId) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing rdv ID')
                ->build($response);
        }

        $body = $request->getParsedBody();

        if (!isset($body['status']) || !is_bool($body['status'])) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid or missing status field. Must be a boolean')
                ->build($response);
        }

        try {
            $this->serviceRdv->updateRdvStatus($rdvId, $body['status']);

            return ApiResponseBuilder::create()
                ->status(200)
                ->data(['message' => 'Appointment status updated successfully'])
                ->build($response);
        } catch (RdvNotFoundException $e) {
            return ApiResponseBuilder::create()
                ->status(404)
                ->error('Appointment not found')
                ->build($response);
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error('An error occurred while updating the appointment')
                ->build($response);
        }
    }
}