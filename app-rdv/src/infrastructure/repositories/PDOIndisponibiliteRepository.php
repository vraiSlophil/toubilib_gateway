<?php

namespace toubilib\infra\repositories;

use DateTimeImmutable;
use PDO;
use toubilib\core\application\ports\spi\repositoryInterfaces\IndisponibiliteRepositoryInterface;
use toubilib\core\domain\entities\Indisponibilite;

final class PDOIndisponibiliteRepository implements IndisponibiliteRepositoryInterface
{
    public function __construct(
        private PDO $pdo
    )
    {

    }

    public function create(Indisponibilite $indisponibilite): void
    {
        $sql = "INSERT INTO indisponibilite (id, praticien_id, debut, fin, motif, created_at) VALUES (:id, :praticien_id, :debut, :fin, :motif, :created_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $indisponibilite->getId(),
            'praticien_id' => $indisponibilite->getPraticienId(),
            'debut' => $indisponibilite->getDebut()->format('Y-m-d H:i:s'),
            'fin' => $indisponibilite->getFin()->format('Y-m-d H:i:s'),
            'motif' => $indisponibilite->getMotif(),
            'created_at' => $indisponibilite->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }

    public function getById(string $id): ?Indisponibilite
    {
        $sql = "SELECT id, praticien_id, debut, fin, motif, created_at FROM indisponibilite WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return $this->hydrateFromRow($row);
    }

    public function listForPraticien(string $praticienId): array
    {
        $sql = "SELECT id, praticien_id, debut, fin, motif, created_at FROM indisponibilite WHERE praticien_id = :praticien_id ORDER BY debut ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['praticien_id' => $praticienId]);

        return array_map(
            fn($row) => $this->hydrateFromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function listForPraticienBetween(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array
    {
        $sql = "SELECT id, praticien_id, debut, fin, motif, created_at FROM indisponibilite WHERE praticien_id = :praticien_id AND debut < :fin AND fin > :debut ORDER BY debut ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'praticien_id' => $praticienId,
            'debut' => $debut->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s')
        ]);

        return array_map(
            fn($row) => $this->hydrateFromRow($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function delete(string $id): void
    {
        $sql = "DELETE FROM indisponibilite WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    private function hydrateFromRow(array $row): Indisponibilite
    {
        return new Indisponibilite(
            $row['id'],
            $row['praticien_id'],
            new DateTimeImmutable($row['debut']),
            new DateTimeImmutable($row['fin']),
            $row['motif'],
            new DateTimeImmutable($row['created_at'])
        );
    }
}

