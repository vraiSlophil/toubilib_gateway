<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

use DateTimeImmutable;
use GuzzleHttp\Client;
use RuntimeException;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\domain\entities\Indisponibilite;

final class HttpIndisponibiliteRepository implements IndisponibiliteRepositoryInterface
{
    public function __construct(
        private Client $client,
        private ?AuthHeaderProvider $authHeaderProvider = null
    ) {
    }

    public function create(Indisponibilite $indisponibilite): void
    {
        $this->client->post(
            'praticiens/' . rawurlencode($indisponibilite->getPraticienId()) . '/indisponibilites',
            [
                'headers' => $this->authHeaders(),
                'json' => [
                    'debut' => $indisponibilite->getDebut()->format(DATE_ATOM),
                    'fin' => $indisponibilite->getFin()->format(DATE_ATOM),
                    'motif' => $indisponibilite->getMotif(),
                ],
            ]
        );
    }

    public function getById(string $id): ?Indisponibilite
    {
        throw new RuntimeException('getById is not supported in RDV service.');
    }

    public function listForPraticien(string $praticienId): array
    {
        $resp = $this->client->get(
            'praticiens/' . rawurlencode($praticienId) . '/indisponibilites',
            ['headers' => $this->authHeaders()]
        );
        if ($resp->getStatusCode() === 404) {
            return [];
        }

        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $out = [];
        foreach ($items as $resource) {
            if (!is_array($resource)) {
                continue;
            }
            $indispo = $this->hydrate($resource);
            if ($indispo !== null) {
                $out[] = $indispo;
            }
        }
        return $out;
    }

    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $indispos = $this->listForPraticien($praticienId);
        return array_values(array_filter(
            $indispos,
            static fn(Indisponibilite $i) => $i->conflictsWith($debut, $fin)
        ));
    }

    public function delete(string $id): void
    {
        throw new RuntimeException('delete is not supported in RDV service.');
    }

    public function update(Indisponibilite $indisponibilite): void
    {
        throw new RuntimeException('update is not supported in RDV service.');
    }

    private function hydrate(array $resource): ?Indisponibilite
    {
        $attributes = $resource['attributes'] ?? $resource;
        if (!is_array($attributes)) {
            return null;
        }

        $id = (string)($resource['id'] ?? ($attributes['id'] ?? ''));
        $praticienId = (string)($attributes['praticienId'] ?? '');
        $debutRaw = $attributes['debut'] ?? null;
        $finRaw = $attributes['fin'] ?? null;
        if ($id === '' || $praticienId === '' || !is_string($debutRaw) || !is_string($finRaw)) {
            return null;
        }

        try {
            $debut = new DateTimeImmutable($debutRaw);
            $fin = new DateTimeImmutable($finRaw);
        } catch (\Exception) {
            return null;
        }

        return new Indisponibilite(
            $id,
            $praticienId,
            $debut,
            $fin,
            isset($attributes['motif']) ? (string)$attributes['motif'] : null
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
