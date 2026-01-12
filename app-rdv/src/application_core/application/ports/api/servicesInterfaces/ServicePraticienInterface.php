<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use toubilib\core\application\ports\api\dtos\outputs\PraticienDetailDTO;
use toubilib\core\application\ports\api\dtos\outputs\PraticienDTO;

interface ServicePraticienInterface
{
    public function listerPraticiens(): array;

    public function getPraticienDetail(string $id): ?PraticienDetailDTO;

    /** @return PraticienDTO[] */
    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array;
}