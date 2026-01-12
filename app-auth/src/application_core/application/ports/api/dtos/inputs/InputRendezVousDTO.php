<?php

namespace toubilib\core\application\ports\api\dtos\inputs;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;

final class InputRendezVousDTO
{
    public function __construct(
        public string $praticienId,
        public string $patientId,
        public ?string $patientEmail,
        public DateTimeImmutable $debut,
        public int $dureeMinutes,
        public string $motifVisite,
    ) {}

    public static function fromArray(array $data): self
    {
        $debutStr = (string)($data['debut'] ?? '');
        try {
            $debut = new DateTimeImmutable($debutStr);

            // IMPORTANT : Convertir l'heure locale vers UTC en gardant la même heure
            // Si on reçoit 14:30+02:00, on veut 14:30+00:00 (pas 12:30+00:00)
            $debutLocal = $debut->format('Y-m-d H:i:s');
            $debut = new DateTimeImmutable($debutLocal, new DateTimeZone('UTC'));

        }
        catch (Throwable) {
            throw new InvalidArgumentException('Must be an ISO-8601 valid date string');
        }

        $duree = filter_var($data['dureeMinutes'] ?? null, FILTER_VALIDATE_INT);
        if ($duree === false || $duree === null) {
            throw new InvalidArgumentException('dureeMinutes must be an integer greater than 0');
        }

        $motifVisite = (string)($data['motifVisite'] ?? '');
        if ($motifVisite === '') {
            throw new InvalidArgumentException('motifVisite is required');
        }

        return new self(
            praticienId: (string)($data['praticienId'] ?? ''),
            patientId: (string)($data['patientId'] ?? ''),
            patientEmail: array_key_exists('patientEmail', $data) ? (string)$data['patientEmail'] : null,
            debut: $debut,
            dureeMinutes: (int)$duree,
            motifVisite: $motifVisite,
        );
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->praticienId === '') {
            $errors['praticienId'] = 'requiered';
        }
        if ($this->patientId === '') {
            $errors['patientId'] = 'requiered';
        }
        if ($this->dureeMinutes <= 0) {
            $errors['dureeMinutes'] = 'must be greater than 0';
        }
        if ($this->patientEmail !== null && !filter_var($this->patientEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['patientEmail'] = 'invalid email format';
        }
        if ($this->motifVisite === '') {
            $errors['motifVisite'] = 'requiered';
        }

        return $errors;
    }
}