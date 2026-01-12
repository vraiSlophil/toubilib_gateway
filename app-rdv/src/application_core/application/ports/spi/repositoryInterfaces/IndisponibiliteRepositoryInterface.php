<?php

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use DateTimeImmutable;
use toubilib\core\domain\entities\Indisponibilite;

interface IndisponibiliteRepositoryInterface
{
    /**
     * Create a new indisponibilite
     */
    public function create(Indisponibilite $indisponibilite): void;

    /**
     * Get an indisponibilite by ID
     */
    public function getById(string $id): ?Indisponibilite;

    /**
     * List all indisponibilites for a praticien
     */
    public function listForPraticien(string $praticienId): array;

    /**
     * List indisponibilites for a praticien between two dates
     */
    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    /**
     * Delete an indisponibilite
     */
    public function delete(string $id): void;
}

