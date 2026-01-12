<?php

namespace toubilib\core\domain\exceptions;

final class IndisponibiliteNotFoundException extends \Exception
{
    public function __construct(string $message = "Indisponibilite not found")
    {
        parent::__construct($message);
    }
}

