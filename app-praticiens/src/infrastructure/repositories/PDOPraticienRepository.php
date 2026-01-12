<?php

namespace toubilib\infra\repositories;

use PDO;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\application\ports\spi\repositoryInterfaces\PraticienRepositoryInterface;
use toubilib\core\domain\entities\MotifVisite;
use toubilib\core\domain\entities\MoyenPaiement;
use toubilib\core\domain\entities\Praticien;
use toubilib\core\domain\entities\Specialite;
use toubilib\core\domain\entities\Structure;
use toubilib\infra\adapters\MonologLogger;
use toubilib\core\domain\entities\PraticienDetail;

final class PDOPraticienRepository implements PraticienRepositoryInterface
{
    public function __construct(
        private PDO                       $pdo,
        private PDORdvRepository $rdvRepository
    )
    {
    }

    public function getAllPraticiens(): array
    {
        $statement = $this->pdo->prepare("
            SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone, 
                   p.rpps_id, p.titre, p.organisation, p.nouveau_patient,
                   s.id as specialite_id, s.libelle as specialite_libelle, s.description as specialite_description
            FROM praticien p 
            LEFT JOIN specialite s ON p.specialite_id = s.id
        ");

        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        $praticiens = [];
        foreach ($results as $row) {
            $praticiens[] = new Praticien(
                $row['id'] ?? 0,
                $row['nom'] ?? 'Inconnu',
                $row['prenom'] ?? 'Inconnu',
                $row['ville'] ?? 'Inconnue',
                $row['email'] ?? 'inconnu@example.com',
                $row['telephone'] ?? '0000000000',
                $row['rpps_id'] ?? 0,
                $row['titre'] ?? 'Non renseigné',
                (bool)$row['nouveau_patient'] ?? false,
                (bool)$row['organisation'] ?? false,
                new Specialite(
                    $row['specialite_id'] ?? 0,
                    $row['specialite_libelle'] ?? 'Non renseignée',
                    $row['specialite_description'] ?? 'Aucune description'
                )
            );
        }

        return $praticiens;
    }

    public function findDetailById(string $id): ?PraticienDetail
    {
        $sql = 'SELECT p.*, s.id AS specialite_id, s.libelle AS specialite_libelle, s.description AS specialite_description,
                       st.id AS structure_id, st.nom AS structure_nom, st.adresse AS structure_adresse,
                       st.ville AS structure_ville, st.code_postal AS structure_code_postal, st.telephone AS structure_telephone
                FROM praticien p
                JOIN specialite s ON s.id = p.specialite_id
                LEFT JOIN structure st ON st.id = p.structure_id
                WHERE p.id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            return null;
        }

        $motifsRows = $this->fetchAllAssoc('SELECT m.id, m.libelle
                                        FROM praticien2motif pm
                                        JOIN motif_visite m ON m.id = pm.motif_id
                                        WHERE pm.praticien_id = :id
                                        ORDER BY m.libelle', [':id' => $id]);

        $moyensRows = $this->fetchAllAssoc('SELECT mp.id, mp.libelle
                                        FROM praticien2moyen pm
                                        JOIN moyen_paiement mp ON mp.id = pm.moyen_id
                                        WHERE pm.praticien_id = :id
                                        ORDER BY mp.libelle', [':id' => $id]);

        $specialite = new Specialite(
            (int)$p['specialite_id'],
            (string)$p['specialite_libelle'],
            $p['specialite_description']
        );

        $structure = $p['structure_id'] ? new Structure(
            (string)$p['structure_id'],
            (string)$p['structure_nom'],
            (string)$p['structure_adresse'],
            (string)$p['structure_ville'],
            (string)$p['structure_code_postal'],
            (string)$p['structure_telephone']
        ) : null;

        $motifs = array_map(
            static fn(array $r) => new MotifVisite((int)$r['id'], (string)$r['libelle']),
            $motifsRows
        );

        $moyens = array_map(
            static fn(array $r) => new MoyenPaiement((int)$r['id'], (string)$r['libelle']),
            $moyensRows
        );

        $rdvs = $this->rdvRepository->listAllForPraticien($id);

        return new PraticienDetail(
            id: (string)$p['id'],
            nom: (string)$p['nom'],
            prenom: (string)$p['prenom'],
            titre: (string)$p['titre'],
            email: (string)$p['email'],
            telephone: (string)$p['telephone'],
            ville: (string)$p['ville'],
            rppsId: $p['rpps_id'] ?: null,
            organisation: ((string)$p['organisation']) === '1' || (string)$p['organisation'] === 't' || (string)$p['organisation'] === 'B\'1\'',
            nouveauPatient: ((string)$p['nouveau_patient']) === '1' || (string)$p['nouveau_patient'] === 't' || (string)$p['nouveau_patient'] === 'B\'1\'',
            specialite: $specialite,
            structure: $structure,
            motifs: $motifs,
            moyens: $moyens,
//            rdvs: $rdvs
        );
    }


    private function fetchAllAssoc(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchPraticiens(?int $specialiteId, ?string $ville): array
    {
        $sql = "
            SELECT p.id, p.nom, p.prenom, p.ville, p.email, p.telephone,
                   p.rpps_id, p.titre, p.organisation, p.nouveau_patient,
                   s.id AS specialite_id, s.libelle AS specialite_libelle, s.description AS specialite_description
            FROM praticien p
            LEFT JOIN specialite s ON p.specialite_id = s.id
            WHERE 1 = 1
        ";
        $params = [];

        if ($specialiteId !== null) {
            $sql .= ' AND p.specialite_id = :specialite';
            $params[':specialite'] = $specialiteId;
        }
        if ($ville !== null && $ville !== '') {
            $sql .= ' AND LOWER(p.ville) = LOWER(:ville)';
            $params[':ville'] = $ville;
        }

        $sql .= ' ORDER BY p.nom, p.prenom';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(static function (array $row): Praticien {
            return new Praticien(
                (string)($row['id'] ?? ''),
                (string)($row['nom'] ?? ''),
                (string)($row['prenom'] ?? ''),
                (string)($row['ville'] ?? ''),
                (string)($row['email'] ?? ''),
                (string)($row['telephone'] ?? ''),
                (string)($row['rpps_id'] ?? ''),
                (string)($row['titre'] ?? 'Dr.'),
                (bool)($row['nouveau_patient'] ?? false),
                (bool)($row['organisation'] ?? false),
                new Specialite(
                    (int)($row['specialite_id'] ?? 0),
                    (string)($row['specialite_libelle'] ?? ''),
                    $row['specialite_description'] ?? null
                )
            );
        }, $rows);
    }
}