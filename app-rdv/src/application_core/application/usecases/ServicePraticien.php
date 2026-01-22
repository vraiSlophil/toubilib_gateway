<?php

namespace toubilib\core\application\usecases;

use Ramsey\Uuid\Uuid;
use toubilib\core\application\ports\api\dtos\inputs\InputPraticienDTO;
use toubilib\core\application\ports\api\dtos\outputs\PraticienDetailDTO;
use toubilib\core\application\ports\api\dtos\outputs\PraticienDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePraticienInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\domain\entities\Praticien;
use toubilib\core\domain\entities\Specialite;
use toubilib\core\domain\exceptions\PraticienNotFoundException;

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
        $detail = $this->praticienRepository->getById($id);
        $this->monologLogger->debug(print_r($detail, true));
        return $detail ? PraticienDetailDTO::fromEntity($detail) : null;
    }

    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array
    {
        $entities = $this->praticienRepository->searchPraticiens($specialiteId, $ville);
        return array_map(static fn($praticien) => PraticienDTO::fromEntity($praticien), $entities);
    }

    public function createPraticien(InputPraticienDTO $input): PraticienDetailDTO
    {
        $id = Uuid::uuid4()->toString();
        $specialite = new Specialite($input->specialiteId, '', null);

        $praticien = new Praticien(
            $id,
            $input->nom,
            $input->prenom,
            $input->ville,
            $input->email,
            $input->telephone,
            $input->rppsId ?? '',
            $input->titre,
            $input->accepteNouveauPatient,
            $input->estOrganisation,
            $specialite
        );

        $this->praticienRepository->create($praticien);

        $detail = $this->praticienRepository->getById($id);
        if ($detail === null) {
            throw new PraticienNotFoundException('Praticien not found after creation');
        }

        return PraticienDetailDTO::fromEntity($detail);
    }

    public function updatePraticien(string $id, InputPraticienDTO $input): PraticienDetailDTO
    {
        $existing = $this->praticienRepository->getById($id);
        if ($existing === null) {
            throw new PraticienNotFoundException('Praticien not found');
        }

        $specialite = new Specialite($input->specialiteId, '', null);
        $praticien = new Praticien(
            $id,
            $input->nom,
            $input->prenom,
            $input->ville,
            $input->email,
            $input->telephone,
            $input->rppsId ?? '',
            $input->titre,
            $input->accepteNouveauPatient,
            $input->estOrganisation,
            $specialite
        );

        $this->praticienRepository->update($praticien);

        $detail = $this->praticienRepository->getById($id);
        if ($detail === null) {
            throw new PraticienNotFoundException('Praticien not found after update');
        }

        return PraticienDetailDTO::fromEntity($detail);
    }

    public function deletePraticien(string $id): void
    {
        $existing = $this->praticienRepository->getById($id);
        if ($existing === null) {
            throw new PraticienNotFoundException('Praticien not found');
        }

        $this->praticienRepository->delete($id);
    }
}
