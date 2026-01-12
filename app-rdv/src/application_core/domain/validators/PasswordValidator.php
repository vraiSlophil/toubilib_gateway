<?php

namespace toubilib\core\domain\validators;

use toubilib\core\domain\exceptions\InvalidPasswordException;

class PasswordValidator
{
    public static function validate(string $password): void
    {
        if (strlen($password) < 8) {
            throw new InvalidPasswordException();
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new InvalidPasswordException();
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new InvalidPasswordException();
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new InvalidPasswordException();
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            throw new InvalidPasswordException();
        }
    }
}