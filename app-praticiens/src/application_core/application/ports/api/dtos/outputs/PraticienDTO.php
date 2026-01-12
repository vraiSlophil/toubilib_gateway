<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\Praticien;

final class PraticienDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $nom,
        public string $prenom,
        public string $ville,
        public string $titre,
        public string $specialite,
        public bool $accepteNouveauPatient,
    )
    {
    }

    public static function fromEntity(Praticien $e): self
    {
        return new self(
            $e->getId(),
            $e->getNom(),
            $e->getPrenom(),
            $e->getVille(),
            $e->getTitre(),
            $e->getSpecialite()->getLibelle(),
            $e->isAccepteNouveauPatient()
        );
    }


    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'ville' => $this->ville,
            'titre' => $this->titre,
            'specialite' => $this->specialite,
            'accepteNouveauPatient' => $this->accepteNouveauPatient,
        ];
    }
}