<?php

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\api\dtos\inputs\InputPatientDTO;
use toubilib\core\application\ports\api\dtos\outputs\PatientDTO;
use toubilib\core\application\ports\api\servicesInterfaces\ServicePatientInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\domain\entities\Patient;
use toubilib\core\domain\exceptions\PatientNotFoundException;

final class ServicePatient implements ServicePatientInterface
{
    public function __construct(private PatientRepositoryInterface $patientRepository)
    {
    }

    public function listPatients(): array
    {
        $patients = $this->patientRepository->listPatients();
        return array_map(static fn(Patient $p) => PatientDTO::fromEntity($p), $patients);
    }

    public function getPatientById(string $id): ?PatientDTO
    {
        $patient = $this->patientRepository->getById($id);
        return $patient ? PatientDTO::fromEntity($patient) : null;
    }

    public function findPatientByEmail(string $email): ?PatientDTO
    {
        $patient = $this->patientRepository->findByEmail($email);
        return $patient ? PatientDTO::fromEntity($patient) : null;
    }

    public function createPatient(InputPatientDTO $input, string $patientId, ?string $email): void
    {
        $patient = new Patient(
            id: $patientId,
            nom: $input->nom,
            prenom: $input->prenom,
            dateNaissance: $input->dateNaissance,
            adresse: $input->adresse,
            codePostal: $input->codePostal,
            ville: $input->ville,
            email: $email ?? $input->email,
            telephone: $input->telephone
        );

        $this->patientRepository->create($patient);
    }

    public function updatePatient(string $patientId, InputPatientDTO $input, ?string $email): void
    {
        $existing = $this->patientRepository->getById($patientId);
        if ($existing === null) {
            throw new PatientNotFoundException('Patient not found');
        }

        $patient = new Patient(
            id: $patientId,
            nom: $input->nom,
            prenom: $input->prenom,
            dateNaissance: $input->dateNaissance,
            adresse: $input->adresse,
            codePostal: $input->codePostal,
            ville: $input->ville,
            email: $email ?? $input->email,
            telephone: $input->telephone
        );

        $this->patientRepository->update($patient);
    }

    public function deletePatient(string $patientId): void
    {
        $existing = $this->patientRepository->getById($patientId);
        if ($existing === null) {
            throw new PatientNotFoundException('Patient not found');
        }

        $this->patientRepository->delete($patientId);
    }
}
