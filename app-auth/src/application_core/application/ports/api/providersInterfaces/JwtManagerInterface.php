<?php

namespace toubilib\core\application\ports\api\providersInterfaces;

interface JwtManagerInterface {
    const ACCESS_TOKEN = 1;
    const REFRESH_TOKEN = 2;

    public function setIssuer(string $issuer): void;
    public function create(array $payload, int $type): string;
    public function validate(string $jwtToken): array;
}