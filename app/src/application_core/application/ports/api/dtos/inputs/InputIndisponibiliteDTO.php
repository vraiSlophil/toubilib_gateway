<?php

namespace toubilib\core\application\ports\api\dtos\inputs;

use DateTimeImmutable;

final class InputIndisponibiliteDTO
{
    public function __construct(
        public readonly string $praticienId,
        public readonly DateTimeImmutable $debut,
        public readonly DateTimeImmutable $fin,
        public readonly ?string $motif = null
    ) {
    }
}

