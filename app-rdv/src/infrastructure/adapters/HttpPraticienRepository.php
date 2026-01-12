<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

use GuzzleHttp\Client;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\domain\entities\Praticien;
use toubilib\core\domain\entities\PraticienDetail;
use toubilib\core\domain\entities\Specialite;
use toubilib\core\domain\entities\Structure;
use toubilib\core\domain\entities\MotifVisite;
use toubilib\core\domain\entities\MoyenPaiement;

/**
 * Adaptateur HTTP Praticiens (exercice 4):
 * Le microservice RDV ne doit plus accéder directement à la DB praticiens.
 * Il appelle le microservice praticiens via HTTP.
 */
final class HttpPraticienRepository implements PraticienRepositoryInterface
{
    public function __construct(private Client $client)
    {
    }

    public function getAllPraticiens(): array
    {
        // Le microservice RDV n'a normalement pas besoin de cette méthode.
        // On conserve une implémentation “best effort”.
        $resp = $this->client->get('praticiens');
        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        return array_map(function (array $p) {
            $specialite = new Specialite(
                (int)($p['specialiteId'] ?? 0),
                (string)($p['specialite'] ?? 'N/A'),
                (string)($p['specialite'] ?? 'N/A')
            );

            return new Praticien(
                (string)($p['id'] ?? ''),
                (string)($p['nom'] ?? ''),
                (string)($p['prenom'] ?? ''),
                (string)($p['ville'] ?? ''),
                (string)($p['email'] ?? ''),
                (string)($p['telephone'] ?? ''),
                (string)($p['rpps_id'] ?? ($p['rppsId'] ?? '')),
                (string)($p['titre'] ?? ''),
                (bool)($p['accepteNouveauPatient'] ?? false),
                (bool)($p['estOrganisation'] ?? false),
                $specialite
            );
        }, is_array($items) ? $items : []);
    }

    public function findDetailById(string $id): ?PraticienDetail
    {
        $resp = $this->client->get('praticiens/' . rawurlencode($id));
        if ($resp->getStatusCode() === 404) {
            return null;
        }

        $data = json_decode((string) $resp->getBody(), true);
        $p = $data['data'] ?? null;
        if (!is_array($p)) {
            return null;
        }

        $specialite = new Specialite(
            (int)($p['specialiteId'] ?? 0),
            (string)($p['specialite'] ?? 'N/A'),
            (string)($p['specialite'] ?? 'N/A')
        );

        $structure = null;
        if (isset($p['structure']) && is_array($p['structure'])) {
            $s = $p['structure'];
            $structure = new Structure(
                (string)($s['id'] ?? ''),
                (string)($s['nom'] ?? ''),
                (string)($s['adresse'] ?? ''),
                (string)($s['ville'] ?? ''),
                (string)($s['telephone'] ?? ''),
                (string)($s['email'] ?? ''),
            );
        }

        $motifs = [];
        if (isset($p['motifs']) && is_array($p['motifs'])) {
            foreach ($p['motifs'] as $m) {
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
        if (isset($p['moyens']) && is_array($p['moyens'])) {
            foreach ($p['moyens'] as $m) {
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
            (string)($p['id'] ?? ''),
            (string)($p['nom'] ?? ''),
            (string)($p['prenom'] ?? ''),
            (string)($p['titre'] ?? ''),
            (string)($p['email'] ?? ''),
            (string)($p['telephone'] ?? ''),
            (string)($p['ville'] ?? ''),
            ($p['rppsId'] ?? null) !== null ? (string)$p['rppsId'] : null,
            (bool)($p['estOrganisation'] ?? false),
            (bool)($p['accepteNouveauPatient'] ?? false),
            $specialite,
            $structure,
            $motifs,
            $moyens
        );
    }

    public function searchPraticiens(?int $specialiteId, ?string $ville): array
    {
        // Best effort identique à getAllPraticiens()
        $query = [];
        if ($specialiteId !== null) {
            $query['specialiteId'] = $specialiteId;
        }
        if ($ville !== null) {
            $query['ville'] = $ville;
        }

        $resp = $this->client->get('praticiens', ['query' => $query]);
        $data = json_decode((string) $resp->getBody(), true);
        $items = $data['data'] ?? [];

        return array_map(function (array $p) {
            $specialite = new Specialite(
                (int)($p['specialiteId'] ?? 0),
                (string)($p['specialite'] ?? 'N/A'),
                (string)($p['specialite'] ?? 'N/A')
            );

            return new Praticien(
                (string)($p['id'] ?? ''),
                (string)($p['nom'] ?? ''),
                (string)($p['prenom'] ?? ''),
                (string)($p['ville'] ?? ''),
                (string)($p['email'] ?? ''),
                (string)($p['telephone'] ?? ''),
                (string)($p['rpps_id'] ?? ($p['rppsId'] ?? '')),
                (string)($p['titre'] ?? ''),
                (bool)($p['accepteNouveauPatient'] ?? false),
                (bool)($p['estOrganisation'] ?? false),
                $specialite
            );
        }, is_array($items) ? $items : []);
    }
}

