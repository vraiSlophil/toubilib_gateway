<?php

namespace toubilib\core\domain\entities;

use DateTimeImmutable;
use InvalidArgumentException;

final class PraticienDetail
{

    public function __construct(
        private string     $id,
        private string     $nom,
        private string     $prenom,
        private string     $titre,
        private string     $email,
        private string     $telephone,
        private string     $ville,
        private ?string    $rppsId,
        private bool       $organisation,
        private bool       $nouveauPatient,
        private Specialite $specialite,
        private ?Structure $structure,
        /** @var MotifVisite[] */
        private array      $motifs,
        /** @var MoyenPaiement[] */
        private array      $moyens,
    )
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function getRppsId(): ?string
    {
        return $this->rppsId;
    }

    public function isOrganisation(): bool
    {
        return $this->organisation;
    }

    public function isNouveauPatient(): bool
    {
        return $this->nouveauPatient;
    }

    public function getSpecialite(): Specialite
    {
        return $this->specialite;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    /** @return MotifVisite[] */
    public function getMotifs(): array
    {
        return $this->motifs;
    }

    /** @return MoyenPaiement[] */
    public function getMoyens(): array
    {
        return $this->moyens;
    }

    public function isAvailable(DateTimeImmutable $debut, DateTimeImmutable $fin): bool
    {
        if ($fin <= $debut) {
            throw new InvalidArgumentException('End time must be after start time');
        }

        if ($debut->format('Y-m-d') !== $fin->format('Y-m-d')) {
            throw new InvalidArgumentException('The slot must be within the same day');
        }

        $jourSemaine = (int)$debut->format('N'); // 1=lundi, 7=dimanche
        if ($jourSemaine > 5) {
            return false;
        }

        $debutJournee = $debut->setTime(8, 0, 0);
        $finJournee = $debut->setTime(19, 0, 0);
        if ($debut < $debutJournee || $fin > $finJournee) {
            return false;
        }
        return true;
    }

    public function isValidMotifVisite(string $motifVisite): bool
    {
        foreach ($this->motifs as $motif) {
            if ($motif->getLibelle() === $motifVisite) {
                return true;
            }
        }
        return false;
    }

}
