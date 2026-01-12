<?php
namespace toubilib\core\application\ports\api\dtos\outputs;

use DateTimeInterface;
use JsonSerializable;
use toubilib\core\domain\entities\Rdv;

final class CreneauDTO implements JsonSerializable
{
    public function __construct(
        public string $id,
        public string $praticienId,
        public string $start, // ISO‑8601
        public string $end    // ISO‑8601
    ) {}

    public static function fromRdv(Rdv $e): self
    {
        return new self(
            $e->getId(),
            $e->getPraticienId(),
            $e->getDebut()->format(DateTimeInterface::ATOM),
            $e->getFin()->format(DateTimeInterface::ATOM)
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'rdvId' => $this->id,
            'praticienId' => $this->praticienId,
            'start' => $this->start,
            'end' => $this->end
        ];
    }
}