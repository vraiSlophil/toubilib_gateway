<?php

namespace toubilib\api\actions;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\core\application\ports\api\dtos\inputs\InputIndisponibiliteDTO;
use toubilib\core\application\usecases\ServiceIndisponibilite;
use toubilib\core\domain\exceptions\IndisponibiliteConflictException;
use toubilib\infra\adapters\ApiResponseBuilder;

final class CreateIndisponibiliteAction
{
    public function __construct(
        private ServiceIndisponibilite $serviceIndisponibilite
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $praticienId = $args['praticienId'];
        $body = $request->getParsedBody();

        // Validation
        if (!isset($body['debut']) || !isset($body['fin'])) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Missing required fields: debut and fin')
                ->build(new Response());
        }

        try {
            $debut = new DateTimeImmutable($body['debut']);
            $fin = new DateTimeImmutable($body['fin']);
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('Invalid date format')
                ->build(new Response());
        }

        if ($fin <= $debut) {
            return ApiResponseBuilder::create()
                ->status(400)
                ->error('End date must be after start date')
                ->build(new Response());
        }

        $input = new InputIndisponibiliteDTO(
            $praticienId,
            $debut,
            $fin,
            $body['motif'] ?? null
        );

        try {
            $id = $this->serviceIndisponibilite->creerIndisponibilite($input);
            $indispo = $this->serviceIndisponibilite->getById($id);

            return ApiResponseBuilder::create()
                ->status(201)
                ->data($indispo)
                ->build(new Response());
        } catch (IndisponibiliteConflictException $e) {
            return ApiResponseBuilder::create()
                ->status(409)
                ->error($e->getMessage())
                ->build(new Response());
        } catch (\Exception $e) {
            return ApiResponseBuilder::create()
                ->status(500)
                ->error($e->getMessage())
                ->build(new Response());
        }
    }
}

