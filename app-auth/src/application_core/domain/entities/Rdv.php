<?php

namespace toubilib\core\domain\entities;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use toubilib\core\application\ports\api\dtos\inputs\InputRendezVousDTO;
use toubilib\core\domain\exceptions\RdvPastCannotBeCancelledException;

final class Rdv
{
    public const STATUS_NOT_OK = 0;
    public const STATUS_OK = 1;

    public function __construct(
        private string             $id,
        private string             $praticienId,
        private string             $patientId,
        private ?string            $patientEmail,
        private DateTimeImmutable  $debut,
        private int                $dureeMinutes,
        private ?DateTimeImmutable $fin,
        private DateTimeImmutable  $dateCreation,
        private int                $status,
        private ?string            $motifVisite
    ) {}

    public static function fromInputDTO(InputRendezVousDTO $inputRendezVousDTO): self
    {
        return new self(
            Uuid::uuid7()->toString(),
            $inputRendezVousDTO->praticienId,
            $inputRendezVousDTO->patientId,
            $inputRendezVousDTO->patientEmail,
            $inputRendezVousDTO->debut,
            $inputRendezVousDTO->dureeMinutes,
            $inputRendezVousDTO->debut->modify('+' . $inputRendezVousDTO->dureeMinutes . ' minutes'),
            new DateTimeImmutable(),
            self::STATUS_NOT_OK,
            $inputRendezVousDTO->motifVisite
        );
    }

    public function getId(): string { return $this->id; }
    public function getPraticienId(): string { return $this->praticienId; }
    public function getPatientId(): string { return $this->patientId; }
    public function getPatientEmail(): ?string { return $this->patientEmail; }
    public function getDebut(): DateTimeImmutable { return $this->debut; }
    public function getDureeMinutes(): int { return $this->dureeMinutes; }
    public function getFin(): DateTimeImmutable { return $this->fin ? $this->fin : $this->debut->modify('+' . $this->dureeMinutes . ' minutes'); }
    public function getDateCreation(): DateTimeImmutable { return $this->dateCreation; }
    public function getStatus(): int { return $this->status; }
    public function getMotifVisite(): ?string { return $this->motifVisite; }

    /**
     * Valide que le rendez-vous peut être annulé (càd supprimé) : il doit être dans le futur.
     * @throws RdvPastCannotBeCancelledException
     */
    public function annuler(DateTimeImmutable $now = new DateTimeImmutable()): void
    {
        if ($this->debut <= $now) {
            throw new RdvPastCannotBeCancelledException("Impossible d'annuler un rendez-vous passé ou en cours.");
        }
        // Pas de changement de statut : la suppression sera faite au niveau repository
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status ? self::STATUS_NOT_OK : self::STATUS_OK;
    }
}