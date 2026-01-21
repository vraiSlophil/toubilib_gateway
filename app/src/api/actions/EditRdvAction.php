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

        if (!array_key_exists('status', $body)) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing status field')
                ->build($response);
        }

        $status = $body['status'];
        if (is_bool($status)) {
            $statusBool = $status;
        } elseif (is_int($status) && ($status === 0 || $status === 1)) {
            $statusBool = (bool)$status;
        } else {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid status field. Must be a boolean or 0/1')
                ->build($response);
        }

        try {
            $this->serviceRdv->updateRdvStatus($rdvId, $statusBool);

            $rdv = $this->serviceRdv->getRdvById($rdvId);
            if ($rdv === null) {
                return ApiResponseBuilder::create()
                    ->status(404)
                    ->error('Appointment not found')
                    ->build($response);
            }

            $attributes = $rdv->jsonSerialize();
            $praticienId = (string)($attributes['praticienId'] ?? '');
            $links = [
                'self' => ['href' => '/api/rdvs/' . $rdvId],
                'praticien' => ['href' => '/api/praticiens/' . $praticienId],
                'cancel' => ['href' => '/api/rdvs/' . $rdvId, 'meta' => ['method' => 'DELETE']]
            ];

            return ApiResponseBuilder::create()
                ->status(200)
                ->data(ApiResponseBuilder::resourceFromAttributes('rdvs', $attributes, 'id', $links))
                ->links(['self' => ['href' => '/api/rdvs/' . $rdvId]])
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
