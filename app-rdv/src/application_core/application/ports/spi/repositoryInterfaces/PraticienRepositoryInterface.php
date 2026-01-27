<?php

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\domain\entities\Praticien;
use toubilib\core\domain\entities\PraticienDetail;

interface PraticienRepositoryInterface
{
    /** @return Praticien[] */
    public function getAllPraticiens(): array;

    public function getById(string $id): ?PraticienDetail;

    public function findByEmail(string $email): ?PraticienDetail;

    /** @return Praticien[] */
    public function searchPraticiens(?int $specialiteId, ?string $ville): array;

    public function create(Praticien $praticien): void;

    public function update(Praticien $praticien): void;

    public function delete(string $id): void;
}
