<?php
// toubilib/src/application_core/application/ports/api/dtos/MotifVisiteDTO.php
namespace toubilib\core\application\ports\api\dtos\outputs;

use JsonSerializable;
use toubilib\core\domain\entities\MotifVisite;

final class MotifVisiteDTO implements JsonSerializable
{
    public function __construct(
        public int $id,
        public string $libelle,
        public ?string $description = null
    ) {}

    public static function fromEntity(MotifVisite $e): self
    {
        $id = $e->getId();
        $lib = $e->getLibelle();

        return new self($id, $lib);
    }

    public function jsonSerialize(): array
    {
        return [
            'libelle' => $this->libelle,
        ];
    }
}
