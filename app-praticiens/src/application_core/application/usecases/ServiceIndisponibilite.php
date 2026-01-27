<?php

namespace toubilib\core\application\usecases;

use DateTimeImmutable;
use toubilib\core\application\ports\api\dtos\inputs\InputIndisponibiliteDTO;
use toubilib\core\application\ports\api\dtos\outputs\IndisponibiliteDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\domain\entities\Indisponibilite;
use toubilib\core\domain\exceptions\IndisponibiliteConflictException;
use toubilib\core\domain\exceptions\IndisponibiliteNotFoundException;

final class ServiceIndisponibilite
{
    public function __construct(
        private IndisponibiliteRepositoryInterface $indisponibiliteRepository
    ) {
    }

    public function creerIndisponibilite(InputIndisponibiliteDTO $input): string
    {
        // Check if there are conflicting indisponibilites
        $existingIndispos = $this->indisponibiliteRepository->listForPraticienBetween(
            $input->praticienId,
            $input->debut,
            $input->fin
        );

        if (count($existingIndispos) > 0) {
            throw new IndisponibiliteConflictException(
                'Cannot create indisponibilite: overlapping with existing indisponibilite'
            );
        }

        $indispo = Indisponibilite::create(
            $input->praticienId,
            $input->debut,
            $input->fin,
            $input->motif
        );

        $this->indisponibiliteRepository->create($indispo);
        return $indispo->getId();
    }

    public function getById(string $id): ?IndisponibiliteDTO
    {
        $indispo = $this->indisponibiliteRepository->getById($id);
        return $indispo ? IndisponibiliteDTO::fromEntity($indispo) : null;
    }

    public function listForPraticien(string $praticienId): array
    {
        $indispos = $this->indisponibiliteRepository->listForPraticien($praticienId);
        return array_map(
            static fn(Indisponibilite $i) => IndisponibiliteDTO::fromEntity($i),
            $indispos
        );
    }

    public function supprimerIndisponibilite(string $id): void
    {
        $indispo = $this->indisponibiliteRepository->getById($id);
        if ($indispo === null) {
            throw new IndisponibiliteNotFoundException('Indisponibilite not found');
        }

        $this->indisponibiliteRepository->delete($id);
    }

    public function updateIndisponibilite(string $id, InputIndisponibiliteDTO $input): IndisponibiliteDTO
    {
        $existing = $this->indisponibiliteRepository->getById($id);
        if ($existing === null) {
            throw new IndisponibiliteNotFoundException('Indisponibilite not found');
        }
        if ($existing->getPraticienId() !== $input->praticienId) {
            throw new IndisponibiliteNotFoundException('Indisponibilite not found');
        }

        $existingIndispos = $this->indisponibiliteRepository->listForPraticienBetween(
            $input->praticienId,
            $input->debut,
            $input->fin
        );
        foreach ($existingIndispos as $indispo) {
            if ($indispo->getId() !== $id) {
                throw new IndisponibiliteConflictException(
                    'Cannot update indisponibilite: overlapping with existing indisponibilite'
                );
            }
        }

        $updated = new Indisponibilite(
            $existing->getId(),
            $existing->getPraticienId(),
            $input->debut,
            $input->fin,
            $input->motif,
            $existing->getCreatedAt()
        );

        $this->indisponibiliteRepository->update($updated);
        return IndisponibiliteDTO::fromEntity($updated);
    }

    /**
     * Check if a praticien has any indisponibilite during a given period
     */
    public function hasIndisponibilite(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): bool
    {
        $indispos = $this->indisponibiliteRepository->listForPraticienBetween($praticienId, $debut, $fin);
        return count($indispos) > 0;
    }
}
