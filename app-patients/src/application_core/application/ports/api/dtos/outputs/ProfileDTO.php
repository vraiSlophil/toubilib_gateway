<?php

namespace toubilib\core\application\ports\api\dtos\outputs;

class ProfileDTO {
    public function __construct(
        public string $ID,
        public string $email,
        public int $role
    ) {}
}