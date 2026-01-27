<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

use DateTimeImmutable;
use DateTimeInterface;
use GuzzleHttp\Client;
use RuntimeException;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\domain\entities\Rdv;

/**
 * Adaptateur HTTP RDV :
 * Le microservice praticiens appelle le microservice RDV via HTTP.
 */
final class HttpRdvRepository implements RdvRepositoryInterface
{
    public function __construct(
        private Client $client,
        private ?AuthHeaderProvider $authHeaderProvider = null
    )
    {
    }

    public function getById(string $rdvId): ?Rdv
    {
        $resp = $this->client->get('rdvs/' . rawurlencode($rdvId), [
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

        return $this->hydrateRdv($resource);
    }

    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $resp = $this->client->get('rdvs', [
            'headers' => $this->authHeaders(),
            'query' => [
                'praticienId' => $praticienId,
                'debut' => $debut->format(DateTimeInterface::ATOM),
                'fin' => $fin->format(DateTimeInterface::ATOM),
            ],
        ]);

        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        $rdvs = [];
        foreach ($items as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            $rdv = $this->hydrateRdv($resource);
            if ($rdv !== null) {
                $rdvs[] = $rdv;
            }
        }

        return $rdvs;
    }

    public function listAllForPraticien(string $praticienId): array
    {
        return $this->listForPraticienBetween(
            $praticienId,
            new DateTimeImmutable('-10 years'),
            new DateTimeImmutable('+10 years')
        );
    }

    public function listForPatient(string $patientId): array
    {
        $resp = $this->client->get('rdvs', [
            'headers' => $this->authHeaders(),
        ]);

        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        $rdvs = [];
        foreach ($items as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            $rdv = $this->hydrateRdv($resource);
            if ($rdv === null) {
                continue;
            }
            if ($patientId !== '' && $rdv->getPatientId() !== $patientId) {
                continue;
            }
            $rdvs[] = $rdv;
        }

        return $rdvs;
    }

    public function create(Rdv $rdv): void
    {
        throw new RuntimeException('Creating RDVs from praticiens service is not supported.');
    }

    public function delete(string $rdvId): void
    {
        throw new RuntimeException('Deleting RDVs from praticiens service is not supported.');
    }

    public function update(Rdv $rdv)
    {
        throw new RuntimeException('Updating RDVs from praticiens service is not supported.');
    }

    private function hydrateRdv(array $resource): ?Rdv
    {
        $attributes = $resource['attributes'] ?? $resource;
        if (!is_array($attributes)) {
            return null;
        }

        $id = (string)($resource['id'] ?? ($attributes['id'] ?? ''));
        if ($id === '') {
            return null;
        }

        $praticienId = (string)($attributes['praticienId'] ?? '');
        $patientId = (string)($attributes['patientId'] ?? '');
        $patientEmail = $attributes['patientEmail'] ?? null;
        $patientEmail = $patientEmail !== null ? (string)$patientEmail : null;

        $debutStr = (string)($attributes['debut'] ?? '');
        if ($debutStr === '') {
            return null;
        }

        try {
            $debut = new DateTimeImmutable($debutStr);
        } catch (\Throwable) {
            return null;
        }

        $fin = null;
        $finStr = $attributes['fin'] ?? null;
        if ($finStr !== null && $finStr !== '') {
            try {
                $fin = new DateTimeImmutable((string) $finStr);
            } catch (\Throwable) {
                $fin = null;
            }
        }

        $dureeMinutes = (int)($attributes['duree'] ?? ($attributes['dureeMinutes'] ?? 0));
        if ($dureeMinutes <= 0 && $fin !== null) {
            $dureeMinutes = (int) max(1, round(($fin->getTimestamp() - $debut->getTimestamp()) / 60));
        }

        $dateCreation = new DateTimeImmutable();
        $dateCreationStr = $attributes['dateCreation'] ?? $attributes['createdAt'] ?? null;
        if ($dateCreationStr !== null && $dateCreationStr !== '') {
            try {
                $dateCreation = new DateTimeImmutable((string) $dateCreationStr);
            } catch (\Throwable) {
                $dateCreation = new DateTimeImmutable();
            }
        }

        $status = (int)($attributes['status'] ?? Rdv::STATUS_NOT_OK);
        $motifVisite = $attributes['motifVisite'] ?? null;
        $motifVisite = $motifVisite !== null ? (string)$motifVisite : null;

        return new Rdv(
            $id,
            $praticienId,
            $patientId,
            $patientEmail,
            $debut,
            $dureeMinutes,
            $fin,
            $dateCreation,
            $status,
            $motifVisite
        );
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
