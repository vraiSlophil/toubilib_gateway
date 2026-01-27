<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use DateTimeImmutable;
use toubilib\core\application\ports\api\dtos\inputs\InputIndisponibiliteDTO;
use toubilib\core\application\ports\api\dtos\outputs\IndisponibiliteDTO;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\domain\entities\Indisponibilite;
use toubilib\core\domain\exceptions\IndisponibiliteConflictException;
use toubilib\core\domain\exceptions\IndisponibiliteNotFoundException;

interface ServiceIndisponibiliteInterface
{
    public function creerIndisponibilite(InputIndisponibiliteDTO $input): string;
    public function getById(string $id): ?IndisponibiliteDTO;
    public function listForPraticien(string $praticienId): array;
    public function supprimerIndisponibilite(string $id): void;
    public function updateIndisponibilite(string $id, InputIndisponibiliteDTO $input): IndisponibiliteDTO;
    public function hasIndisponibilite(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): bool;
}
