<?php

namespace toubilib\core\domain\entities\auth;

final class Credentials
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $password
    ) {}

    public function getUserId(): string { return $this->userId; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }

}