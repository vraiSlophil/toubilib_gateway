<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use DateTimeImmutable;
use toubilib\core\application\ports\api\dtos\inputs\InputRendezVousDTO;
use toubilib\core\application\ports\api\dtos\outputs\CreneauDTO;
use toubilib\core\application\ports\api\dtos\outputs\RendezVousDTO;
use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;

interface ServiceRdvInterface
{
    public function getRdvById(string $rdvId): ?RendezVousDTO;

    public function listCreneauxPris(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    public function creerRdv(InputRendezVousDTO $input): string;

    public function annulerRendezVous(string $rdvId): void;

    public function listAgendaForPraticien(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array;

    public function listRdvsForUser(ProfileDTO $user, bool $pastOnly = false): array;

    public function updateRdvStatus(string $rdvId, bool $status);
}