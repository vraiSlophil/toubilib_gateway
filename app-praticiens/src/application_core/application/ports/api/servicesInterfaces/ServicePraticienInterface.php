<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use toubilib\core\application\ports\api\dtos\outputs\PraticienDetailDTO;
use toubilib\core\application\ports\api\dtos\outputs\PraticienDTO;
use toubilib\core\application\ports\api\dtos\inputs\InputPraticienDTO;

interface ServicePraticienInterface
{
    public function listerPraticiens(): array;

    public function getPraticienDetail(string $id): ?PraticienDetailDTO;

    public function findPraticienByEmail(string $email): ?PraticienDTO;

    /** @return PraticienDTO[] */
    public function rechercherPraticiens(?int $specialiteId, ?string $ville): array;

    public function createPraticien(InputPraticienDTO $input): PraticienDetailDTO;

    public function updatePraticien(string $id, InputPraticienDTO $input): PraticienDetailDTO;

    public function deletePraticien(string $id): void;
}
