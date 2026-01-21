<?php

namespace toubilib\core\application\ports\api\dtos\inputs;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;

final class InputPatientDTO
{
    public function __construct(
        public string $nom,
        public string $prenom,
        public ?DateTimeImmutable $dateNaissance,
        public ?string $adresse,
        public ?string $codePostal,
        public ?string $ville,
        public ?string $email,
        public string $telephone
    ) {}

    public static function fromArray(array $data): self
    {
        $nom = trim((string)($data['nom'] ?? ''));
        $prenom = trim((string)($data['prenom'] ?? ''));
        $telephone = trim((string)($data['telephone'] ?? ''));
        $adresse = array_key_exists('adresse', $data) ? trim((string)$data['adresse']) : null;
        $codePostal = array_key_exists('codePostal', $data) ? trim((string)$data['codePostal']) : null;
        $ville = array_key_exists('ville', $data) ? trim((string)$data['ville']) : null;
        $email = array_key_exists('email', $data) ? trim((string)$data['email']) : null;

        $dateInput = null;
        if (array_key_exists('dateNaissance', $data)) {
            $dateInput = $data['dateNaissance'];
        } elseif (array_key_exists('date_naissance', $data)) {
            $dateInput = $data['date_naissance'];
        }

        $dateNaissance = null;
        if ($dateInput !== null && $dateInput !== '') {
            try {
                $dateNaissance = new DateTimeImmutable((string)$dateInput);
            } catch (Throwable) {
                throw new InvalidArgumentException('dateNaissance must be a valid date string');
            }
        }

        return new self(
            $nom,
            $prenom,
            $dateNaissance,
            $adresse !== '' ? $adresse : null,
            $codePostal !== '' ? $codePostal : null,
            $ville !== '' ? $ville : null,
            $email !== '' ? $email : null,
            $telephone
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
        if ($this->telephone === '') {
            $errors['telephone'] = 'required';
        }
        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'invalid email format';
        }

        return $errors;
    }
}
