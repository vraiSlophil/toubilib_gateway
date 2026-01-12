<?php

namespace toubilib\core\application\usecases;


use toubilib\core\application\ports\api\dtos\outputs\PraticienDetailDTO;
use toubilib\core\application\ports\api\dtos\outputs\PraticienDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\infra\adapters\MonologLogger;

final class ServicePraticien implements ServicePraticienInterface
{
    private PraticienRepositoryInterface $praticienRepository;
    private MonologLoggerInterface $monologLogger;

    public function __construct(PraticienRepositoryInterface $praticienRepository, MonologLoggerInterface $MonologLogger)
    {
        $this->praticienRepository = $praticienRepository;
        $this->monologLogger = $MonologLogger;
    }

    public function listerPraticiens(): array
    {
       $praticiens = $this->praticienRepository->getAllPraticiens();

        return array_map(
            fn($praticien) => PraticienDTO::fromEntity($praticien),
            $praticiens
        );
    }

    public function getPraticienDetail(string $id): ?PraticienDetailDTO
    {
        $detail = $this->praticienRepository->findDetailById($id);
        $this->monologLogger->debug(print_r($detail, true));
        return $detail ? PraticienDetailDTO::fromEntity($detail) : null;
    }

    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array
    {
        $entities = $this->praticienRepository->searchPraticiens($specialiteId, $ville);
        return array_map(static fn($praticien) => PraticienDTO::fromEntity($praticien), $entities);
    }
}