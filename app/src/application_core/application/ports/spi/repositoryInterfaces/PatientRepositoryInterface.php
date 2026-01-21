<?php

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\domain\entities\Patient;

interface PatientRepositoryInterface
{
    /** @return Patient[] */
    public function listPatients(): array;

    public function getById(string $id): ?Patient;

    public function create(Patient $patient): void;

    public function update(Patient $patient): void;

    public function delete(string $id): void;
}
