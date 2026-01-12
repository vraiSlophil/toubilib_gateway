<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\MotifVisite;
use toubilib\core\domain\entities\MoyenPaiement;
use toubilib\core\domain\entities\PraticienDetail;

final class PraticienDetailDTO implements JsonSerializable
{

    public function __construct(
        public string        $id,
        public string        $nom,
        public string        $prenom,
        public string        $titre,
        public string        $email,
        public string        $telephone,
        public string        $ville,
        public ?string       $rppsId,
        public bool          $organisation,
        public bool          $nouveauPatient,
        public SpecialiteDTO $specialite,
        public StructureDTO  $structure,
        public array         $motifs,
        public array         $moyens
    )
    {

    }

    public static function fromEntity(PraticienDetail $praticien): self
    {
        return new self(
            $praticien->getId(),
            $praticien->getNom(),
            $praticien->getPrenom(),
            $praticien->getTitre(),
            $praticien->getEmail(),
            $praticien->getTelephone(),
            $praticien->getVille(),
            $praticien->getRppsId(),
            $praticien->isOrganisation(),
            $praticien->isNouveauPatient(),
            SpecialiteDTO::fromEntity($praticien->getSpecialite()) ?? null,
            StructureDTO::fromEntity($praticien->getStructure()),
            array_map(fn(MotifVisite $m) => MotifVisiteDTO::fromEntity($m), $praticien->getMotifs()),
            array_map(fn(MoyenPaiement $m) => MoyenPaiementDTO::fromEntity($m), $praticien->getMoyens())
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'titre' => $this->titre,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'ville' => $this->ville,
            'rppsId' => $this->rppsId,
            'organisation' => $this->organisation,
            'nouveauPatient' => $this->nouveauPatient,
            'specialite' => $this->specialite,
            'structure' => $this->structure,
            'motifs' => $this->motifs,
            'moyens' => $this->moyens
        ];
    }
}