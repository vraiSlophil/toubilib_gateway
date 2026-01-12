<?php

namespace toubilib\core\application\ports\api\dtos\outputs;
// PHP
use JsonSerializable;
use toubilib\core\domain\entities\Structure;

final class StructureDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $nom,
        public string $adresse,
        public ?string $ville,
        public ?string $codePostal,
        public ?string $telephone
    ) {}

    public static function fromEntity(Structure $e): self
    {
        return new self(
            $e->getId(),
            $e->getNom(),
            $e->getAdresse(),
            $e->getVille(),
            $e->getCodePostal(),
            $e->getTelephone()
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'codePostal' => $this->codePostal,
            'telephone' => $this->telephone,
        ];
    }
}