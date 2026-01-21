<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\Patient;

final class PatientDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $nom,
        public string $prenom,
        public ?string $dateNaissance,
        public ?string $adresse,
        public ?string $codePostal,
        public ?string $ville,
        public ?string $email,
        public string $telephone
    ) {}

    public static function fromEntity(Patient $patient): self
    {
        return new self(
            $patient->getId(),
            $patient->getNom(),
            $patient->getPrenom(),
            $patient->getDateNaissance()?->format('Y-m-d'),
            $patient->getAdresse(),
            $patient->getCodePostal(),
            $patient->getVille(),
            $patient->getEmail(),
            $patient->getTelephone()
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'dateNaissance' => $this->dateNaissance,
            'adresse' => $this->adresse,
            'codePostal' => $this->codePostal,
            'ville' => $this->ville,
            'email' => $this->email,
            'telephone' => $this->telephone,
        ];
    }
}
