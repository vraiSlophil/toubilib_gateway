<?php
namespace toubilib\core\domain\entities;

final class MotifVisite
{
    public function __construct(
        private int $id,
        private string $libelle
    ) {}

    public function getId(): int { return $this->id; }
    public function getLibelle(): string { return $this->libelle; }
}
