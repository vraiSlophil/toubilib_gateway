<?php

namespace toubilib\core\domain\entities;

use DateTimeImmutable;

final class Indisponibilite
{
    private string $id;
    private string $praticienId;
    private DateTimeImmutable $debut;
    private DateTimeImmutable $fin;
    private ?string $motif;
    private DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $praticienId,
        DateTimeImmutable $debut,
        DateTimeImmutable $fin,
        ?string $motif = null,
        ?DateTimeImmutable $createdAt = null
    ) {
        if ($fin <= $debut) {
            throw new \InvalidArgumentException('End date must be after start date');
        }

        $this->id = $id;
        $this->praticienId = $praticienId;
        $this->debut = $debut;
        $this->fin = $fin;
        $this->motif = $motif;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
    }

    public static function create(
        string $praticienId,
        DateTimeImmutable $debut,
        DateTimeImmutable $fin,
        ?string $motif = null
    ): self {
        return new self(
            \Ramsey\Uuid\Uuid::uuid7()->toString(),
            $praticienId,
            $debut,
            $fin,
            $motif
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getPraticienId(): string
    {
        return $this->praticienId;
    }

    public function getDebut(): DateTimeImmutable
    {
        return $this->debut;
    }

    public function getFin(): DateTimeImmutable
    {
        return $this->fin;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Check if this indisponibilite conflicts with a given time period
     */
    public function conflictsWith(DateTimeImmutable $debut, DateTimeImmutable $fin): bool
    {
        return $this->debut < $fin && $this->fin > $debut;
    }
}

