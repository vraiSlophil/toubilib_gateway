<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\Rdv;

final class RendezVousDTO implements JsonSerializable
{
    public function __construct(
        public string  $id,
        public string  $praticienId,
        public string  $patientId,
        public ?string $patientEmail,
        public string  $debut, // ISO‑8601
        public string  $fin,   // ISO‑8601
        public int     $status,
        public int     $duree,
        public ?string $motifVisite
    )
    {
    }

    public static function fromEntity(Rdv $e): self
    {
        return new self(
            $e->getId(),
            $e->getPraticienId(),
            $e->getPatientId(),
            $e->getPatientEmail(),
            $e->getDebut()->format(\DateTimeInterface::ATOM),
            $e->getFin()->format(\DateTimeInterface::ATOM),
            $e->getStatus(),
            $e->getDureeMinutes(),
            $e->getMotifVisite()
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'praticienId' => $this->praticienId,
            'patientId' => $this->patientId,
            'patientEmail' => $this->patientEmail,
            'debut' => $this->debut,
            'fin' => $this->fin,
            'status' => $this->status,
            'duree' => $this->duree,
            'motifVisite' => $this->motifVisite
        ];
    }
}