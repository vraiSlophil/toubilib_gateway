<?php

namespace toubilib\core\application\ports\api\dtos\outputs;
// PHP
use JsonSerializable;
use toubilib\core\domain\entities\MoyenPaiement;

final class MoyenPaiementDTO implements JsonSerializable
{
    public function __construct(
        public readonly int $id,
        public readonly string $libelle
    ) {}

    public static function fromEntity(MoyenPaiement $e): self
    {
        return new self($e->getId(), $e->getLibelle());
    }

    public function jsonSerialize(): array
    {
        return [
            'libelle' => $this->libelle
        ];
    }
}