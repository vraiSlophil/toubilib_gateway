<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

use GuzzleHttp\Client;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\domain\entities\MotifVisite;
use toubilib\core\domain\entities\MoyenPaiement;
use toubilib\core\domain\entities\Praticien;
use toubilib\core\domain\entities\PraticienDetail;
use toubilib\core\domain\entities\Specialite;
use toubilib\core\domain\entities\Structure;

/**
 * Adaptateur HTTP Praticiens :
 * Le microservice RDV appelle le microservice praticiens via HTTP.
 */
final class HttpPraticienRepository implements PraticienRepositoryInterface
{
    public function __construct(
        private Client $client,
        private ?AuthHeaderProvider $authHeaderProvider = null
    )
    {
    }

    /** @return Praticien[] */
    public function getAllPraticiens(): array
    {
        $resp = $this->client->get('praticiens', [
            'headers' => $this->authHeaders(),
        ]);
        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn(array $resource) => $this->hydratePraticien($resource),
            $items
        )));
    }

    public function getById(string $id): ?PraticienDetail
    {
        $resp = $this->client->get('praticiens/' . rawurlencode($id), [
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

        return $this->hydratePraticienDetail($resource);
    }

    /** @return Praticien[] */
    public function searchPraticiens(?int $specialiteId, ?string $ville): array
    {
        $query = [];
        if ($specialiteId !== null) {
            $query['specialiteId'] = $specialiteId;
        }
        if ($ville !== null) {
            $query['ville'] = $ville;
        }

        $resp = $this->client->get('praticiens', [
            'query' => $query,
            'headers' => $this->authHeaders(),
        ]);
        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        if (!is_array($items)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn(array $resource) => $this->hydratePraticien($resource),
            $items
        )));
    }

    public function create(Praticien $praticien): void
    {
        $this->client->post('praticiens', [
            'headers' => $this->authHeaders(),
            'json' => $this->praticienPayload($praticien),
        ]);
    }

    public function update(Praticien $praticien): void
    {
        $this->client->put('praticiens/' . rawurlencode($praticien->getId()), [
            'headers' => $this->authHeaders(),
            'json' => $this->praticienPayload($praticien),
        ]);
    }

    public function delete(string $id): void
    {
        $this->client->delete('praticiens/' . rawurlencode($id), [
            'headers' => $this->authHeaders(),
        ]);
    }

    private function hydratePraticien(array $resource): ?Praticien
    {
        $attributes = $resource['attributes'] ?? $resource;
        if (!is_array($attributes)) {
            return null;
        }

        $id = (string)($resource['id'] ?? ($attributes['id'] ?? ''));
        if ($id === '') {
            return null;
        }

        $specialiteData = $attributes['specialite'] ?? null;
        $specialiteId = (int)($attributes['specialiteId'] ?? 0);
        $specialiteLibelle = 'N/A';
        $specialiteDescription = null;
        if (is_array($specialiteData)) {
            $specialiteId = (int)($specialiteData['id'] ?? $specialiteId);
            $specialiteLibelle = (string)($specialiteData['libelle'] ?? 'N/A');
            $specialiteDescription = isset($specialiteData['description'])
                ? (string)$specialiteData['description']
                : null;
        } elseif ($specialiteData !== null) {
            $specialiteLibelle = (string)$specialiteData;
        }

        $specialite = new Specialite($specialiteId, $specialiteLibelle, $specialiteDescription);

        return new Praticien(
            $id,
            (string)($attributes['nom'] ?? ''),
            (string)($attributes['prenom'] ?? ''),
            (string)($attributes['ville'] ?? ''),
            (string)($attributes['email'] ?? ''),
            (string)($attributes['telephone'] ?? ''),
            (string)($attributes['rppsId'] ?? ($attributes['rpps_id'] ?? '')),
            (string)($attributes['titre'] ?? ''),
            (bool)($attributes['accepteNouveauPatient'] ?? false),
            (bool)($attributes['estOrganisation'] ?? false),
            $specialite
        );
    }

    private function hydratePraticienDetail(array $resource): ?PraticienDetail
    {
        $attributes = $resource['attributes'] ?? $resource;
        if (!is_array($attributes)) {
            return null;
        }

        $id = (string)($resource['id'] ?? ($attributes['id'] ?? ''));
        if ($id === '') {
            return null;
        }

        $specialiteData = $attributes['specialite'] ?? null;
        $specialiteId = (int)($attributes['specialiteId'] ?? 0);
        $specialiteLibelle = 'N/A';
        $specialiteDescription = null;
        if (is_array($specialiteData)) {
            $specialiteId = (int)($specialiteData['id'] ?? $specialiteId);
            $specialiteLibelle = (string)($specialiteData['libelle'] ?? 'N/A');
            $specialiteDescription = isset($specialiteData['description'])
                ? (string)$specialiteData['description']
                : null;
        } elseif ($specialiteData !== null) {
            $specialiteLibelle = (string)$specialiteData;
        }

        $specialite = new Specialite($specialiteId, $specialiteLibelle, $specialiteDescription);

        $structure = null;
        if (isset($attributes['structure']) && is_array($attributes['structure'])) {
            $s = $attributes['structure'];
            $structure = new Structure(
                (string)($s['id'] ?? ''),
                (string)($s['nom'] ?? ''),
                (string)($s['adresse'] ?? ''),
                ($s['ville'] ?? null) !== null ? (string)$s['ville'] : null,
                ($s['codePostal'] ?? $s['code_postal'] ?? null) !== null ? (string)($s['codePostal'] ?? $s['code_postal']) : null,
                ($s['telephone'] ?? null) !== null ? (string)$s['telephone'] : null,
            );
        }

        $motifs = [];
        if (isset($attributes['motifs']) && is_array($attributes['motifs'])) {
            foreach ($attributes['motifs'] as $m) {
                if (!is_array($m)) {
                    continue;
                }
                $motifs[] = new MotifVisite(
                    (int)($m['id'] ?? 0),
                    (string)($m['libelle'] ?? ''),
                    (int)($m['duree'] ?? ($m['dureeMinutes'] ?? 0))
                );
            }
        }

        $moyens = [];
        if (isset($attributes['moyens']) && is_array($attributes['moyens'])) {
            foreach ($attributes['moyens'] as $m) {
                if (!is_array($m)) {
                    continue;
                }
                $moyens[] = new MoyenPaiement(
                    (int)($m['id'] ?? 0),
                    (string)($m['libelle'] ?? '')
                );
            }
        }

        return new PraticienDetail(
            $id,
            (string)($attributes['nom'] ?? ''),
            (string)($attributes['prenom'] ?? ''),
            (string)($attributes['titre'] ?? ''),
            (string)($attributes['email'] ?? ''),
            (string)($attributes['telephone'] ?? ''),
            (string)($attributes['ville'] ?? ''),
            ($attributes['rppsId'] ?? null) !== null ? (string)$attributes['rppsId'] : null,
            (bool)($attributes['organisation'] ?? $attributes['estOrganisation'] ?? false),
            (bool)($attributes['nouveauPatient'] ?? $attributes['accepteNouveauPatient'] ?? false),
            $specialite,
            $structure,
            $motifs,
            $moyens
        );
    }

    private function praticienPayload(Praticien $praticien): array
    {
        return [
            'nom' => $praticien->getNom(),
            'prenom' => $praticien->getPrenom(),
            'ville' => $praticien->getVille(),
            'email' => $praticien->getEmail(),
            'telephone' => $praticien->getTelephone(),
            'specialiteId' => $praticien->getSpecialite()->getId(),
            'rppsId' => $praticien->getRppsId(),
            'titre' => $praticien->getTitre(),
            'accepteNouveauPatient' => $praticien->isAccepteNouveauPatient(),
            'estOrganisation' => $praticien->isEstOrganisation(),
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
