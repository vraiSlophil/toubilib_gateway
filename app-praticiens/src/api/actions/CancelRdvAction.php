<?php

namespace toubilib\api\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\domain\exceptions\RdvNotFoundException;
use Throwable;
use toubilib\core\domain\exceptions\RdvPastCannotBeCancelledException;
use toubilib\infra\adapters\ApiResponseBuilder;
use toubilib\infra\adapters\MonologLogger;

final class CancelRdvAction
{
    public function __construct(
        private ServiceRdvInterface $serviceRdv,
        private MonologLoggerInterface $logger
    )
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $rdvId = $args['rdvId'] ?? null;
        if (!$rdvId) {
            return ApiResponseBuilder::create()->status(400)->error('Missing rdvId parameter')->build($response);
        }
        try {
            $this->serviceRdv->annulerRendezVous($rdvId);
        } catch (RdvNotFoundException $e) {
            return ApiResponseBuilder::create()->status(404)->error('Rdv not found', $e)->build($response);
        } catch (RdvPastCannotBeCancelledException $e) {
            return ApiResponseBuilder::create()->status(400)->error('Rdv past cannot be cancelled', $e)->build($response);
        } catch (Throwable $e) {
            $this->logger->error('Error cancelling rdv: ' . $e->getMessage(), ['exception' => $e]);
            return ApiResponseBuilder::create()->status(500)->error('Internal server error', $e)->build($response);
        }
        // 204 No Content (no body)
        return ApiResponseBuilder::create()->status(204)->build($response);
    }
}