<?php
namespace toubilib\infra\repositories;

use DateTimeImmutable;
use Exception;
use PDO;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use toubilib\core\application\ports\spi\repositoryInterfaces\RdvRepositoryInterface;
use toubilib\core\domain\entities\Rdv;

final class PDORdvRepository implements RdvRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function getById(string $rdvId): ?Rdv
    {
        $sql = 'SELECT id, praticien_id, patient_id, patient_email, date_heure_debut, duree, date_heure_fin, date_creation, status, motif_visite
                FROM rdv WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $rdvId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $sql = 'SELECT id, praticien_id, patient_id, patient_email, date_heure_debut, duree, date_heure_fin, date_creation, status, motif_visite
                FROM rdv WHERE ';
        if ($praticienId !== '') {
            $sql .= 'praticien_id = :pid AND ';
        }
        $sql .= 'date_heure_debut < :fin
                 AND (date_heure_fin IS NULL OR date_heure_fin > :debut)
                 ORDER BY date_heure_debut ASC';

        $stmt = $this->pdo->prepare($sql);
        $params = [
            ':debut' => $debut->format('Y-m-d H:i:sP'),
            ':fin'   => $fin->format('Y-m-d H:i:sP'),
        ];
        if ($praticienId !== '') {
            $params[':pid'] = $praticienId;
        }
        $stmt->execute($params);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->map($row);
        }
        return $out;
    }

    public function listAllForPraticien(string $praticienId): array
    {
        $debut = new DateTimeImmutable('0001-01-01 00:00:00');
        $fin   = new DateTimeImmutable('9999-12-31 23:59:59');
        return $this->listForPraticienBetween($praticienId, $debut, $fin);
    }

    public function listForPatient(string $patientId): array
    {
        $sql = 'SELECT id, praticien_id, patient_id, patient_email, date_heure_debut, duree, date_heure_fin, date_creation, status, motif_visite
                FROM rdv WHERE patient_id = :pid ORDER BY date_heure_debut DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':pid' => $patientId]);

        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->map($row);
        }

        return $out;
    }

    private function map(array $r): Rdv
    {
        try {
            return new Rdv(
                id: (string)$r['id'],
                praticienId: (string)$r['praticien_id'],
                patientId: (string)$r['patient_id'],
                patientEmail: $r['patient_email'] ?? null,
                debut: new DateTimeImmutable((string)$r['date_heure_debut']),
                dureeMinutes: (int)$r['duree'],
                fin: $r['date_heure_fin'] ? new DateTimeImmutable((string)$r['date_heure_fin']) : null,
                dateCreation: isset($r['date_creation']) ? new DateTimeImmutable((string)$r['date_creation']) : new DateTimeImmutable(),
                status: (int)$r['status'],
                motifVisite: $r['motif_visite'] ?? null
            );
        } catch (Exception $e) {
            throw new RuntimeException('Failed to map RDV entity from database row.', 0, $e);
        }
    }

    public function create(Rdv $rdv): void
    {
        $sql = 'INSERT INTO rdv (id, praticien_id, patient_id, patient_email, date_heure_debut, duree, date_heure_fin, date_creation, motif_visite)
                VALUES (:id, :praticien_id, :patient_id, :patient_email, :date_heure_debut, :duree, :date_heure_fin, :date_creation, :motif_visite)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'               => $rdv->getId(),
            ':praticien_id'     => $rdv->getPraticienId(),
            ':patient_id'       => $rdv->getPatientId(),
            ':patient_email'    => $rdv->getPatientEmail(),
            ':date_heure_debut' => $rdv->getDebut()->format('Y-m-d H:i:sP'),
            ':duree'            => $rdv->getDureeMinutes(),
            ':date_heure_fin'   => $rdv->getFin()?->format('Y-m-d H:i:sP'),
            ':date_creation'    => date('Y-m-d H:i:sP'),
            ':motif_visite'     => $rdv->getMotifVisite(),
        ]);
    }

    public function delete(string $rdvId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM rdv WHERE id = :id');
        $stmt->execute([':id' => $rdvId]);
    }

    public function update(Rdv $rdv)
    {
        $sql = 'UPDATE rdv SET
                    praticien_id = :praticien_id,
                    patient_id = :patient_id,
                    patient_email = :patient_email,
                    date_heure_debut = :date_heure_debut,
                    duree = :duree,
                    date_heure_fin = :date_heure_fin,
                    status = :status,
                    motif_visite = :motif_visite
                WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id'               => $rdv->getId(),
            ':praticien_id'     => $rdv->getPraticienId(),
            ':patient_id'       => $rdv->getPatientId(),
            ':patient_email'    => $rdv->getPatientEmail(),
            ':date_heure_debut' => $rdv->getDebut()->format('Y-m-d H:i:sP'),
            ':duree'            => $rdv->getDureeMinutes(),
            ':date_heure_fin'   => $rdv->getFin()?->format('Y-m-d H:i:sP'),
            ':status'           => $rdv->getStatus(),
            ':motif_visite'     => $rdv->getMotifVisite(),
        ]);
    }
}