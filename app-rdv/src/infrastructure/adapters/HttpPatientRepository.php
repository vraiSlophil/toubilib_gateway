<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

use DateTimeImmutable;
use GuzzleHttp\Client;
use toubilib\core\application\ports\spi\repositoryInterfaces\PatientRepositoryInterface;
use toubilib\core\domain\entities\Patient;

/**
 * Adaptateur HTTP Patients :
 * Le microservice RDV appelle le microservice patients via HTTP.
 */
final class HttpPatientRepository implements PatientRepositoryInterface
{
    public function __construct(
        private Client $client,
        private ?AuthHeaderProvider $authHeaderProvider = null
    )
    {
    }

    /** @return Patient[] */
    public function listPatients(): array
    {
        $resp = $this->client->get('patients', [
            'headers' => $this->authHeaders(),
        ]);
        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn(array $resource) => $this->hydratePatient($resource),
            $items
        )));
    }

    public function getById(string $id): ?Patient
    {
        $resp = $this->client->get('patients/' . rawurlencode($id), [
            'headers' => $this->authHeaders(),
        ]);
        if ($resp->getStatusCode() === 404) {
            return null;
        }

        $data = json_decode((string) $resp->getBody(), true);
        $resource = $data['data'] ?? null;
        if (!is_array($resource)) {
            return null;
        }

        return $this->hydratePatient($resource);
    }

    public function findByEmail(string $email): ?Patient
    {
        $resp = $this->client->get('patients', [
            'query' => ['email' => $email],
            'headers' => $this->authHeaders(),
        ]);

        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];
        if (!is_array($items) || $items === []) {
            return null;
        }

        $resource = $items[0];
        if (!is_array($resource)) {
            return null;
        }

        return $this->hydratePatient($resource);
    }

    public function create(Patient $patient): void
    {
        $this->client->post('patients', [
            'headers' => $this->authHeaders(),
            'json' => $this->patientPayload($patient),
        ]);
    }

    public function update(Patient $patient): void
    {
        $this->client->put('patients/' . rawurlencode($patient->getId()), [
            'headers' => $this->authHeaders(),
            'json' => $this->patientPayload($patient),
        ]);
    }

    public function delete(string $id): void
    {
        $this->client->delete('patients/' . rawurlencode($id), [
            'headers' => $this->authHeaders(),
        ]);
    }

    public function findById(string $id): ?Patient
    {
        return $this->getById($id);
    }

    private function hydratePatient(array $resource): ?Patient
    {
        $attributes = $resource['attributes'] ?? $resource;
        if (!is_array($attributes)) {
            return null;
        }

        $id = (string)($resource['id'] ?? ($attributes['id'] ?? ''));
        if ($id === '') {
            return null;
        }

        $dateNaissance = $this->parseDate($attributes['dateNaissance'] ?? null);

        return new Patient(
            $id,
            (string)($attributes['nom'] ?? ''),
            (string)($attributes['prenom'] ?? ''),
            $dateNaissance,
            isset($attributes['adresse']) ? (string)$attributes['adresse'] : null,
            isset($attributes['codePostal']) ? (string)$attributes['codePostal'] : null,
            isset($attributes['ville']) ? (string)$attributes['ville'] : null,
            isset($attributes['email']) ? (string)$attributes['email'] : null,
            (string)($attributes['telephone'] ?? '')
        );
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function patientPayload(Patient $patient): array
    {
        return [
            'nom' => $patient->getNom(),
            'prenom' => $patient->getPrenom(),
            'dateNaissance' => $patient->getDateNaissance()?->format('Y-m-d'),
            'adresse' => $patient->getAdresse(),
            'codePostal' => $patient->getCodePostal(),
            'ville' => $patient->getVille(),
            'email' => $patient->getEmail(),
            'telephone' => $patient->getTelephone(),
        ];
    }

    private function authHeaders(): array
    {
        $authorization = $this->authHeaderProvider?->getAuthorization();
        if ($authorization === null || $authorization === '') {
            return [];
        }

        return ['Authorization' => $authorization];
    }
}
