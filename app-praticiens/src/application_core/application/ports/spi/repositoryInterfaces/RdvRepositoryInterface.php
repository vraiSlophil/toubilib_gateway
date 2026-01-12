<?php

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use DateTimeImmutable;
use toubilib\core\domain\entities\Rdv;

interface RdvRepositoryInterface
{
    public function getById(string $rdvId): ?Rdv;

    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    public function listAllForPraticien(string $praticienId): array;

    public function listForPatient(string $patientId): array;

    public function create(Rdv $rdv): void;

    public function delete(string $rdvId): void;

    public function update(Rdv $rdv);
}