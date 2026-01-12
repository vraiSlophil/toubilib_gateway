<?php

namespace toubilib\core\application\usecases;

use DateTimeImmutable;
use toubilib\core\application\ports\api\dtos\inputs\InputRendezVousDTO;
use toubilib\core\application\ports\api\dtos\outputs\CreneauDTO;
use toubilib\core\application\ports\api\dtos\outputs\RendezVousDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServiceRdvInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\domain\entities\Rdv;
use toubilib\core\domain\exceptions\RdvNotFoundException;
use toubilib\core\domain\exceptions\PraticienNotFoundException;
use toubilib\core\domain\exceptions\InvalidMotifException;
use toubilib\core\domain\exceptions\SlotConflictException;
use toubilib\core\domain\exceptions\PraticienUnavailableException;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\domain\entities\Roles;

final class ServiceRdv implements ServiceRdvInterface
{
    public function __construct(
        private RdvRepositoryInterface       $rdvRepository,
        private PraticienRepositoryInterface $praticienRepository,
        private MonologLoggerInterface       $logger
    )
    {
    }

    public function getRdvById(string $rdvId): ?RendezVousDTO
    {
        $rdv = $this->rdvRepository->getById($rdvId);
        return $rdv ? RendezVousDTO::fromEntity($rdv) : null;
    }

    public function listCreneauxPris(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $rdvs = $this->rdvRepository->listForPraticienBetween($praticienId, $debut, $fin);
        return array_map(static fn(Rdv $e) => CreneauDTO::fromRdv($e), $rdvs);
    }

    public function listAgendaForPraticien(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $rdvs = $this->rdvRepository->listForPraticienBetween($praticienId, $debut, $fin);
        return array_map(static fn(Rdv $e) => RendezVousDTO::fromEntity($e), $rdvs);
    }

    public function creerRdv(InputRendezVousDTO $input): string
    {
        $praticien = $this->praticienRepository->findDetailById($input->praticienId);
        if ($praticien === null) {
            throw new PraticienNotFoundException('Praticien not found');
        }

        $fin = $input->debut->modify('+' . $input->dureeMinutes . ' minutes');

        // NB ex.4: le contrôle des indisponibilités est géré côté microservice praticiens.
        // Ici, on contrôle seulement:
        // - conflits avec les RDVs existants (DB RDV)
        // - disponibilité “horaire” du praticien (via microservice praticiens)

        $existants = $this->rdvRepository->listForPraticienBetween(
            $input->praticienId,
            $input->debut->modify('-1 minute'),
            $fin->modify('+1 minute')
        );
        foreach ($existants as $rdvExistant) {
            if ($rdvExistant->getDebut() < $fin && $rdvExistant->getFin() > $input->debut) {
                throw new SlotConflictException('Slot conflict');
            }
        }

        if (!$praticien->isAvailable($input->debut, $fin)) {
            throw new PraticienUnavailableException('Praticien unavailable');
        }

        $rdv = Rdv::fromInputDTO($input);
        $this->rdvRepository->create($rdv);
        return $rdv->getId();
    }

    public function annulerRendezVous(string $rdvId): void
    {
        $rdv = $this->rdvRepository->getById($rdvId);
        if ($rdv === null) {
            throw new RdvNotFoundException('Rdv not found');
        }
        $rdv->annuler();
        $this->rdvRepository->delete($rdvId);
        $this->logger->log('info', 'Rdv cancelled', ['rdv_id' => $rdvId]);
    }

    public function listRdvsForUser(ProfileDTO $user, bool $pastOnly = false): array
    {
        return match ($user->role) {
            Roles::PRATICIEN => array_map(
                static fn(Rdv $rdv) => RendezVousDTO::fromEntity($rdv),
                $this->rdvRepository->listForPraticienBetween(
                    $user->ID,
                    $pastOnly ? new DateTimeImmutable('-10 years') : new DateTimeImmutable('-1 year'),
                    $pastOnly ? new DateTimeImmutable() : new DateTimeImmutable('+1 year')
                )
            ),
            Roles::PATIENT => array_map(
                static fn(Rdv $rdv) => RendezVousDTO::fromEntity($rdv),
                array_filter(
                    $this->rdvRepository->listForPatient($user->ID),
                    static fn(Rdv $rdv) => !$pastOnly || $rdv->getFin() < new DateTimeImmutable()
                )
            ),
            default => [],
        };
    }


    public function listRdvsFiltered(ProfileDTO $user, ?\DateTimeImmutable $debut, ?\DateTimeImmutable $fin, ?string $praticienId, bool $pastOnly): array
    {
        $debutDefault = $pastOnly ? new DateTimeImmutable('-10 years') : new DateTimeImmutable('-1 year');
        $finDefault = $pastOnly ? new DateTimeImmutable() : new DateTimeImmutable('+1 year');

        return match ($user->role) {
            Roles::PRATICIEN => array_map(
                static fn(Rdv $rdv) => RendezVousDTO::fromEntity($rdv),
                $this->rdvRepository->listForPraticienBetween(
                    $praticienId ?? $user->ID,
                    $debut ?? $debutDefault,
                    $fin ?? $finDefault
                )
            ),
            Roles::PATIENT => array_map(
                static fn(Rdv $rdv) => RendezVousDTO::fromEntity($rdv),
                array_filter(
                    $this->rdvRepository->listForPatient($user->ID),
                    static function (Rdv $rdv) use ($debut, $fin, $praticienId, $pastOnly) {
                        // Filter by pastOnly
                        if ($pastOnly && $rdv->getFin() >= new DateTimeImmutable()) {
                            return false;
                        }

                        // Filter by debut
                        if ($debut !== null && $rdv->getDebut() < $debut) {
                            return false;
                        }

                        // Filter by fin
                        if ($fin !== null && $rdv->getDebut() > $fin) {
                            return false;
                        }

                        // Filter by praticienId
                        if ($praticienId !== null && $rdv->getPraticienId() !== $praticienId) {
                            return false;
                        }

                        return true;
                    }
                )
            ),
            default => [],
        };
    }

    public function updateRdvStatus(string $rdvId, bool $status): void
    {
        $rdv = $this->rdvRepository->getById($rdvId);
        if ($rdv === null) {
            throw new RdvNotFoundException('Rdv not found');
        }

        $rdv->setStatus($status);
        $this->rdvRepository->update($rdv);
    }
}