<?php

namespace toubilib\core\application\ports\api\dtos\inputs;

class CredentialsDTO {
    public string $email;
    public string $password;

    public function __construct(string $email, string $password) {
        $this->email = $email;
        $this->password = $password;
    }
}