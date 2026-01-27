<?php

namespace toubilib\core\application\usecases;

use toubilib\core\application\ports\api\dtos\outputs\ProfileDTO;
use toubilib\core\application\ports\api\servicesInterfaces\AuthzServiceInterface;
use toubilib\core\application\ports\spi\adapterInterface\MonologLoggerInterface;
use toubilib\core\domain\entities\Roles;

final class AuthzService implements AuthzServiceInterface
{
    public function __construct(
        private MonologLoggerInterface $monologLogger
    ) {}

    public function canAccessPraticienAgenda(ProfileDTO $user, string $praticienId): bool
    {
        if ($praticienId === '') {
            return false;
        }

        return $user->role === Roles::PRATICIEN && $user->ID === $praticienId;
    }

    public function canAccessRdvDetails(ProfileDTO $user, string $rdvId): bool
    {
        return false;
    }

    public function canCancelRdv(ProfileDTO $user, string $rdvId): bool
    {
        return $this->canAccessRdvDetails($user, $rdvId);
    }

    public function canCreateRdv(ProfileDTO $user): bool
    {
        return $user->role === Roles::PATIENT;
    }

    public function canListUserRdvs(ProfileDTO $user): bool
    {

        $this->monologLogger->log('info', $user->role);

        return in_array($user->role, [Roles::PATIENT, Roles::PRATICIEN], true);
    }

    public function canEditRdv(ProfileDTO $user, string $rdvId): bool
    {
        return false;
    }

    public function canManageIndisponibilites(ProfileDTO $user, string $praticienId): bool
    {
        if ($praticienId === '') {
            return false;
        }

        return $user->role === Roles::PRATICIEN && $user->ID === $praticienId;
    }

}
