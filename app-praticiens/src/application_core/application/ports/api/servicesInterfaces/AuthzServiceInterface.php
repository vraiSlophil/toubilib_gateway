<?php

namespace toubilib\core\application\ports\api\servicesInterfaces;

use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;

interface AuthzServiceInterface
{
    public function canAccessPraticienAgenda(ProfileDTO $user, string $praticienId): bool;
    public function canAccessRdvDetails(ProfileDTO $user, string $rdvId): bool;
    public function canCancelRdv(ProfileDTO $user, string $rdvId): bool;
    public function canCreateRdv(ProfileDTO $user): bool;
    public function canListUserRdvs(ProfileDTO $user): bool;
    public function canEditRdv(ProfileDTO $user, string $rdvId): bool;

}