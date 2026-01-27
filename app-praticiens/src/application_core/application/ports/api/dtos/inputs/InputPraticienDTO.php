<?php

namespace toubilib\core\application\ports\api\dtos\inputs;

use InvalidArgumentException;

final class InputPraticienDTO
{
    public function __construct(
        public string $nom,
        public string $prenom,
        public string $ville,
        public string $email,
        public string $telephone,
        public int $specialiteId,
        public ?string $rppsId,
        public string $titre,
        public bool $accepteNouveauPatient,
        public bool $estOrganisation,
        public ?string $structureId
    ) {}

    public static function fromArray(array $data): self
    {
        $nom = trim((string)($data['nom'] ?? ''));
        $prenom = trim((string)($data['prenom'] ?? ''));
        $ville = trim((string)($data['ville'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $telephone = trim((string)($data['telephone'] ?? ''));
        $titre = trim((string)($data['titre'] ?? 'Dr.'));
        $rppsId = array_key_exists('rppsId', $data) ? trim((string)$data['rppsId']) : null;
        if ($rppsId === '') {
            $rppsId = null;
        }

        $structureId = null;
        if (array_key_exists('structureId', $data)) {
            $structureId = trim((string)$data['structureId']);
        } elseif (array_key_exists('structure_id', $data)) {
            $structureId = trim((string)$data['structure_id']);
        }
        if ($structureId === '') {
            $structureId = null;
        }

        $specialiteId = $data['specialiteId'] ?? null;
        if ($specialiteId === null || $specialiteId === '') {
            throw new InvalidArgumentException('specialiteId is required');
        }
        if (!is_numeric($specialiteId)) {
            throw new InvalidArgumentException('specialiteId must be numeric');
        }

        $accepteNouveauPatient = filter_var(
            $data['accepteNouveauPatient'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );
        if ($accepteNouveauPatient === null) {
            $accepteNouveauPatient = false;
        }

        $estOrganisation = filter_var(
            $data['estOrganisation'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );
        if ($estOrganisation === null) {
            $estOrganisation = false;
        }

        return new self(
            $nom,
            $prenom,
            $ville,
            $email,
            $telephone,
            (int)$specialiteId,
            $rppsId,
            $titre !== '' ? $titre : 'Dr.',
            (bool)$accepteNouveauPatient,
            (bool)$estOrganisation,
            $structureId
        );
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->nom === '') {
            $errors['nom'] = 'required';
        }
        if ($this->prenom === '') {
            $errors['prenom'] = 'required';
        }
        if ($this->ville === '') {
            $errors['ville'] = 'required';
        }
        if ($this->email === '') {
            $errors['email'] = 'required';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'invalid email format';
        }
        if ($this->telephone === '') {
            $errors['telephone'] = 'required';
        }
        if ($this->specialiteId <= 0) {
            $errors['specialiteId'] = 'required';
        }

        return $errors;
    }
}
