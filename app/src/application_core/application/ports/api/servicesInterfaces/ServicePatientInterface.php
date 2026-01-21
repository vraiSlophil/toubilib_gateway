<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use toubilib\core\application\ports\api\dtos\inputs\InputPatientDTO;
use toubilib\core\application\ports\api\dtos\outputs\PatientDTO;

interface ServicePatientInterface
{
    /** @return PatientDTO[] */
    public function listPatients(): array;

    public function getPatientById(string $id): ?PatientDTO;

    public function createPatient(InputPatientDTO $input, string $patientId, ?string $email): void;

    public function updatePatient(string $patientId, InputPatientDTO $input, ?string $email): void;

    public function deletePatient(string $patientId): void;
}
