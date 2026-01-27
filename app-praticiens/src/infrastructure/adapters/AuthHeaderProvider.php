<?php

declare(strict_types=1);

namespace toubilib\infra\adapters;

final class AuthHeaderProvider
{
    private ?string $authorization = null;

    public function setAuthorization(?string $authorization): void
    {
        $value = $authorization !== null ? trim($authorization) : '';
        $this->authorization = $value !== '' ? $value : null;
    }

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }
}
