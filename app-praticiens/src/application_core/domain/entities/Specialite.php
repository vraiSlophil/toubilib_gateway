<?php

namespace toubilib\core\domain\entities;

final class Specialite
{
    private int $id;
    private string $libelle;
    private ?string $description;

    public function __construct(int $id, string $libelle, ?string $description)
    {
        $this->id = $id;
        $this->libelle = $libelle;
        $this->description = $description;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}