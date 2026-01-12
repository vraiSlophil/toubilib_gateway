<?php
// toubilib/src/application_core/application/ports/api/dtos/SpecialiteDTO.php
namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\Specialite;

final class SpecialiteDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $libelle,
        public ?string $description
    ) {}

    public static function fromEntity(Specialite $e): self
    {
        return new self($e->getId(), $e->getLibelle(), $e->getDescription());
    }

    public function jsonSerialize(): array
    {
        return [
            'libelle' => $this->libelle,
            'description' => $this->description,
        ];
    }
}
