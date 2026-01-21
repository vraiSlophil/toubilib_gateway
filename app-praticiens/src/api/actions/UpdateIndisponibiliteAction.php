<?php

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use toubilib\core\application\ports\api\dtos\inputs\InputIndisponibiliteDTO;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\core\domain\exceptions\IndisponibiliteConflictException;
use toubilib\core\domain\exceptions\IndisponibiliteNotFoundException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class UpdateIndisponibiliteAction
{
    public function __construct(
        private ServiceIndisponibilite $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'] ?? '';
        $indispoId = $args['indispoId'] ?? '';
        $body = $request->getParsedBody();

        if ($praticienId === '' || $indispoId === '') {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing praticienId or indispoId')
                ->build($response);
        }

        if (!isset($body['debut']) || !isset($body['fin'])) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing required fields: debut and fin')
                ->build($response);
        }

        try {
            $debut = new DateTimeImmutable($body['debut']);
            $fin = new DateTimeImmutable($body['fin']);
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid date format')
                ->build($response);
        }

        if ($fin <= $debut) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('End date must be after start date')
                ->build($response);
        }

        $input = new InputIndisponibiliteDTO(
            $praticienId,
            $debut,
            $fin,
            $body['motif'] ?? null
        );

        try {
            $updated = $this->serviceIndisponibilite->updateIndisponibilite($indispoId, $input);

            return ApiResponseBuilder::create()
                ->status(200)
                ->data($updated)
                ->build($response);
        } catch (IndisponibiliteNotFoundException $e) {
            return ApiResponseBuilder::create()
                ->status(404)
                ->error($e->getMessage())
                ->build($response);
        } catch (IndisponibiliteConflictException $e) {
            return ApiResponseBuilder::create()
                ->status(409)
                ->error($e->getMessage())
                ->build($response);
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error($e->getMessage())
                ->build($response);
        }
    }
}
