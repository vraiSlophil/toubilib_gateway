<?php

namespace toubilib\core\application\ports\spi\repositoryInterfaces;

use toubilib\core\domain\entities\User;

interface AuthRepositoryInterface {
    public function byEmail(string $email): User;
    public function byId(string $id): User;
    public function save(User $user): void;
    public function delete(string $id): void;
    public function update(User $user): void;
}
