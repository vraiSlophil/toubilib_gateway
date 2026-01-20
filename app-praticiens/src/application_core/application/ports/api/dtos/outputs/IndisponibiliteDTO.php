<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

use DateTimeImmutable;
use JsonSerializable;
use toubilib\core\domain\entities\Indisponibilite;

final class IndisponibiliteDTO implements JsonSerializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $praticienId,
        public readonly DateTimeImmutable $debut,
        public readonly DateTimeImmutable $fin,
        public readonly ?string $motif
    ) {
    }

    public static function fromEntity(Indisponibilite $indispo): self
    {
        return new self(
            $indispo->getId(),
            $indispo->getPraticienId(),
            $indispo->getDebut(),
            $indispo->getFin(),
            $indispo->getMotif()
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'praticienId' => $this->praticienId,
            'debut' => $this->debut->format('c'),
            'fin' => $this->fin->format('c'),
            'motif' => $this->motif,
            '_links' => [
                'self' => [
                    'href' => "/api/praticiens/{$this->praticienId}/indisponibilites/{$this->id}"
                ],
                'delete' => [
                    'href' => "/api/praticiens/{$this->praticienId}/indisponibilites/{$this->id}",
                    'method' => 'DELETE'
                ]
            ]
        ];
    }
}

