<?php

namespace toubilib\core\domain\entities;


final class Praticien
{
    private string $id;
    private string $nom;
    private string $prenom;
    private string $ville;
    private string $email;
    private string $telephone;
    private string $rpps_id;
    private string $titre;
    private bool $accepte_nouveau_patient;
    private bool $est_organisation;
    private Specialite $specialite;

    public function __construct(string $id, string $nom, string $prenom, string $ville, string $email, string $telephone, string $rpps_id, string $titre, bool $accepte_nouveau_patient, bool $est_organisation, Specialite $specialite)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->ville = $ville;
        $this->email = $email;
        $this->telephone = $telephone;
        $this->rpps_id = $rpps_id;
        $this->titre = $titre;
        $this->accepte_nouveau_patient = $accepte_nouveau_patient;
        $this->est_organisation = $est_organisation;
        $this->specialite = $specialite;
    }

    public function isEstOrganisation(): bool
    {
        return $this->est_organisation;
    }

    public function setEstOrganisation(bool $est_organisation): void
    {
        $this->est_organisation = $est_organisation;
    }

    public function isAccepteNouveauPatient(): bool
    {
        return $this->accepte_nouveau_patient;
    }

    public function setAccepteNouveauPatient(bool $accepte_nouveau_patient): void
    {
        $this->accepte_nouveau_patient = $accepte_nouveau_patient;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getRppsId(): string
    {
        return $this->rpps_id;
    }

    public function setRppsId(string $rpps_id): void
    {
        $this->rpps_id = $rpps_id;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): void
    {
        $this->ville = $ville;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getSpecialite(): Specialite
    {
        return $this->specialite;
    }

    public function setSpecialite(Specialite $specialite): void
    {
        $this->specialite = $specialite;
    }
}