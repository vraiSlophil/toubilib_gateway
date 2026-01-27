<?php

namespace toubilib\infra\repositories;

use DateTimeImmutable;
use PDO;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\domain\entities\Patient;

final class PDOPatientRepository implements PatientRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function listPatients(): array
    {
        $sql = 'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone
                FROM patient
                ORDER BY nom, prenom';
        $stmt = $this->pdo->query($sql);
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return array_map(fn(array $row) => $this->map($row), $rows);
    }

    public function getById(string $id): ?Patient
    {
        $sql = 'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone
                FROM patient WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->map($row) : null;
    }

    public function findByEmail(string $email): ?Patient
    {
        $sql = 'SELECT id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone
                FROM patient WHERE LOWER(email) = LOWER(:email)
                LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->map($row) : null;
    }

    public function create(Patient $patient): void
    {
        $sql = 'INSERT INTO patient (id, nom, prenom, date_naissance, adresse, code_postal, ville, email, telephone)
                VALUES (:id, :nom, :prenom, :date_naissance, :adresse, :code_postal, :ville, :email, :telephone)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $patient->getId(),
            ':nom' => $patient->getNom(),
            ':prenom' => $patient->getPrenom(),
            ':date_naissance' => $patient->getDateNaissance()?->format('Y-m-d'),
            ':adresse' => $patient->getAdresse(),
            ':code_postal' => $patient->getCodePostal(),
            ':ville' => $patient->getVille(),
            ':email' => $patient->getEmail(),
            ':telephone' => $patient->getTelephone(),
        ]);
    }

    public function update(Patient $patient): void
    {
        $sql = 'UPDATE patient SET
                    nom = :nom,
                    prenom = :prenom,
                    date_naissance = :date_naissance,
                    adresse = :adresse,
                    code_postal = :code_postal,
                    ville = :ville,
                    email = :email,
                    telephone = :telephone
                WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $patient->getId(),
            ':nom' => $patient->getNom(),
            ':prenom' => $patient->getPrenom(),
            ':date_naissance' => $patient->getDateNaissance()?->format('Y-m-d'),
            ':adresse' => $patient->getAdresse(),
            ':code_postal' => $patient->getCodePostal(),
            ':ville' => $patient->getVille(),
            ':email' => $patient->getEmail(),
            ':telephone' => $patient->getTelephone(),
        ]);
    }

    public function delete(string $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM patient WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    private function map(array $row): Patient
    {
        $dateNaissance = null;
        if (!empty($row['date_naissance'])) {
            $dateNaissance = new DateTimeImmutable((string)$row['date_naissance']);
        }

        return new Patient(
            id: (string)$row['id'],
            nom: (string)$row['nom'],
            prenom: (string)$row['prenom'],
            dateNaissance: $dateNaissance,
            adresse: $row['adresse'] !== null ? (string)$row['adresse'] : null,
            codePostal: $row['code_postal'] !== null ? (string)$row['code_postal'] : null,
            ville: $row['ville'] !== null ? (string)$row['ville'] : null,
            email: $row['email'] !== null ? (string)$row['email'] : null,
            telephone: (string)$row['telephone']
        );
    }
}
