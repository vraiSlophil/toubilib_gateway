<?php

namespace toubilib\core\domain\exceptions;

final class IndisponibiliteConflictException extends \Exception
{
    public function __construct(string $message = "Indisponibilite conflicts with existing one")
    {
        parent::__construct($message);
    }
}

