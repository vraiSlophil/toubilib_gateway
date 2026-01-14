# Toubilib \- API de prise de rendez\-vous médicaux

## 1\. Présentation

Toubilib est une application de gestion de rendez\-vous médicaux.  
Ce dépôt contient **exclusivement le backend** du projet, exposé sous la forme d'une **API RESTful** destinée à être
consommée par :

* un front Web patient / praticien,
* une application mobile,
* et, plus généralement, tout client HTTP conforme.

Le backend est développé en **PHP** avec le framework **Slim**, et organisé selon une **architecture hexagonale** (
ports / adapters) respectant l'**inversion de dépendances**.  
Il s'appuie sur plusieurs bases de données distinctes (patients, rendez\-vous, praticiens, authentification), pouvant
être exécutées dans des conteneurs Docker séparés.

L'API :

* expose des ressources REST (praticiens, patients, rendez\-vous, indisponibilités, authentification),
* échange toutes les données au format **JSON**, en incluant des liens **HATEOAS**,
* gère l'authentification via des tokens **JWT**,
* applique une politique d'autorisations selon le rôle (patient / praticien),
* traite les erreurs et exceptions de manière centralisée,
* gère les en\-têtes **CORS** pour l'accès cross\-origin.

Le projet est entièrement dockerisé et se lance via `docker compose`.  
L'API HTTP est exposée sous le préfixe `/api`.

---

## 2\. Architecture

L'organisation générale suit les principes d'architecture hexagonale :

* `app/src/application_core`: coeur métier (use cases, dtos, ports, services)
    * `application/ports/api`: DTOs et interfaces orientées API
    * `application/ports/spi`: interfaces de persistance et d'adapters
    * `application/usecases`: cas d'usage (`ServiceRdv`, `ServicePraticien`, `ServiceIndisponibilite`, `AuthnService`,
      `AuthzService`, ...)
    * `domain`: entités métiers (`Rdv`, `Praticien`, `PraticienDetail`, `Indisponibilite`, `User`, `Roles`, ...)
* `app/src/infrastructure`: implémentations concrètes (adapters)
    * `repositories`: `PDOAuthRepository`, `PDORdvRepository`, `PDOPraticienRepository`,
      `PDOIndisponibiliteRepository`, ...
    * `adapters`: `ApiResponseBuilder`, logger, etc.
    * `api`: routes Slim, middlewares, actions
* `app/src/api/routes.php`: définition des routes HTTP de l'API.

Les use cases ne dépendent que des ports (interfaces) et des entités de domaine, jamais des implémentations concrètes.

---

## 3. Lancement du projet

### 3.1 Prérequis

* Docker
* Docker Compose

### 3.2 Configuration initiale

Avant de lancer le projet, vous devez créer le fichier de configuration `.env` à partir du modèle `.env.dist` :

```bash
cp app/config/.env.dist app/config/.env
```

Vous pouvez ensuite modifier `app/config/.env` si nécessaire (par exemple, changer les mots de passe ou ajuster
`JWT_SECRET`).

### 3.3 Démarrage des conteneurs

À la racine du projet, lancez :

```bash
docker compose --env-file ./app/config/.env up --build
```

Cette commande va :

* construire l'image `api.toubilib`,
* lancer le conteneur PHP qui expose l'application,
* lancer les 4 bases PostgreSQL :
    * `toubiprati.db` (praticiens) sur le port `5432`,
    * `toubiauth.db` (authentification) sur le port `5433`,
    * `toubirdv.db` (rendez-vous) sur le port `5434`,
    * `toubipat.db` (patients) sur le port `5435`,
* exposer l'API sur le port `6080`,
* exposer Adminer (administration SQL) sur le port `8080`.

L'API sera accessible sur :

```text
http://localhost:6080/api
```

L'interface Adminer sera accessible sur :

```text
http://localhost:8080
```

### 3.4 Initialisation des bases de données

Une fois les conteneurs démarrés, vous devez importer le schéma et les données de chaque base via **Adminer** :

1. Accédez à [http://localhost:8080](http://localhost:8080)
2. Connectez-vous à chaque base avec les identifiants définis dans `app/config/.env`.
3. Pour chaque base, importez le fichier SQL correspondant depuis le dossier `sql/`.

---

## 4\. Authentification et rôles

L'API gère deux types de profils:

* `Roles::PATIENT`
* `Roles::PRATICIEN`

Après authentification, l'API expose un profil via `ProfileDTO`:

```json
{
  "ID": "uuid\-utilisateur",
  "email": "user@example.com",
  "role": 1
}
```

Les rôles servent à :

* contrôler les autorisations via `AuthzService` (consultation / annulation / création de RDV, gestion des
  indisponibilités, etc.),
* filtrer les données renvoyées (ex. `listRdvsFiltered`).

Les endpoints protégés nécessitent un mécanisme d'authentification fourni par `AuthnMiddleware` (typiquement un JWT
passé dans le header `Authorization: Bearer ...`).

---

## 5\. Documentation des routes

### 5\.1 Racine

#### `GET /api/`

**Description**: Vérifie que l'API est vivante (route racine).  
**Auth**: aucune.

**Réponse 200**: payload générique (ex. message ou liens de découverte), format:

```json
{
  "data": {
    ...
  },
  "_links": {
    ...
  }
}
```

---

### 5\.2 Authentification

#### 5\.2\.1 `POST /api/auth/signin`

**Description**: connexion d'un utilisateur existant.

**Corps JSON** (`CredentialsDTO`):

```json
{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Réponse 200** (`AuthDTO`):

```json
{
  "data": {
    "profile": {
      "ID": "uuid",
      "email": "user@example.com",
      "role": 1
    },
    "access_token": "jwt\-access\-token",
    "refresh_token": "jwt\-refresh\-token"
  }
}
```

**Codes d'erreur possibles**:

* `401` \- identifiants invalides
* `400` \- corps mal formé

---

#### 5\.2\.2 `POST /api/auth/signup`

**Description**: inscription d'un nouvel utilisateur.

**Corps JSON** (`CredentialsDTO`):

```json
{
  "email": "newuser@example.com",
  "password": "Motdepasse1\!"
}
```

**Règles**:

* `PasswordValidator` vérifie la robustesse du mot de passe.
* Le `role` peut être passé (selon votre Action) ou défini côté serveur.

**Réponses possibles**:

* `201` \- utilisateur créé, renvoie `ProfileDTO`
* `409` \- email déjà utilisé (`DuplicateEmailException`)
* `400` \- validation échouée (mot de passe trop faible, etc.)

---

### 5\.3 Praticiens

#### 5\.3\.1 `GET /api/praticiens`

**Description**: liste des praticiens (résumé).

**Auth**: aucune.

**Réponse 200**: tableau de `PraticienDTO`:

```json
{
  "data": [
    {
      "id": "uuid",
      "nom": "Dupont",
      "prenom": "Jean",
      "ville": "Paris",
      "titre": "Dr",
      "specialite": "Généraliste",
      "accepteNouveauPatient": true
    }
  ]
}
```

---

#### 5\.3\.2 `GET /api/praticiens/{praticienId}`

**Description**: détail d'un praticien.

**Auth**: aucune pour la consultation publique du détail.

**Paramètres de chemin**:

* `praticienId` \- id du praticien (UUID/chaine).

**Réponse 200**: `PraticienDetailDTO`:

```json
{
  "data": {
    "id": "uuid",
    "nom": "Dupont",
    "prenom": "Jean",
    "titre": "Dr",
    "email": "dr.dupont@example.com",
    "telephone": "0102030405",
    "ville": "Paris",
    "rppsId": "1234567890",
    "organisation": false,
    "nouveauPatient": true,
    "specialite": {
      "libelle": "Généraliste",
      "description": "Médecine générale"
    },
    "structure": {
      "id": "1",
      "nom": "Cabinet Médical",
      "adresse": "1 rue de la Paix",
      "ville": "Paris",
      "codePostal": "75000",
      "telephone": "0102030405"
    },
    "motifs": [
      {
        "libelle": "Consultation"
      }
    ],
    "moyens": [
      {
        "libelle": "Carte Vitale"
      }
    ]
  }
}
```

**404**: praticien inconnu.

---

### 5\.4 Créneaux pris (agenda praticien)

#### 5\.4\.1 `GET /api/praticiens/{praticienId}/rdvs`

**Description**: liste des créneaux (rdv) déjà pris pour un praticien dans une période donnée.

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('viewAgenda')`  
Uniquement accessible par le praticien correspondant (via `AuthzService::canAccessPraticienAgenda`).

**Query parameters**(typique, à adapter dans votre Action):

* `debut` \- ISO\-8601 (`2025\-01\-01T00:00:00Z`)
* `fin` \- ISO\-8601

**Réponse 200**: tableau de `CreneauDTO`:

```json
{
  "data": [
    {
      "rdvId": "uuid",
      "praticienId": "uuid\-praticien",
      "start": "2025\-01\-01T08:00:00+00:00",
      "end": "2025\-01\-01T08:30:00+00:00"
    }
  ]
}
```

---

### 5\.5 Indisponibilités praticien

Toutes ces routes sont protégées par `AuthnMiddleware` \+ `AuthzMiddleware('manageIndisponibilites')`  
Elles ne sont accessibles que par le praticien propriétaire.

#### 5\.5\.1 `GET /api/praticiens/{praticienId}/indisponibilites`

**Description**: liste des indisponibilités d'un praticien.

**Réponse 200**: tableau de `IndisponibiliteDTO`:

```json
{
  "data": [
    {
      "id": "uuid",
      "praticienId": "uuid\-praticien",
      "debut": "2025\-01\-10T09:00:00+00:00",
      "fin": "2025\-01\-10T12:00:00+00:00",
      "motif": "Congé",
      "_links": {
        "self": {
          "href": "/api/praticiens/{praticienId}/indisponibilites/{id}"
        },
        "delete": {
          "href": "/api/praticiens/{praticienId}/indisponibilites/{id}",
          "method": "DELETE"
        }
      }
    }
  ]
}
```

---

#### 5\.5\.2 `POST /api/praticiens/{praticienId}/indisponibilites`

**Description**: création d'une indisponibilité.

**Corps JSON** (`InputIndisponibiliteDTO`):

```json
{
  "debut": "2025\-01\-10T09:00:00",
  "fin": "2025\-01\-10T12:00:00",
  "motif": "Congé"
}
```

(`praticienId` est dans l'URL, ou dans le body selon votre Action.)

**Règles métier** (`ServiceIndisponibilite`):

* Impossible de créer une indisponibilité si des RDV existent sur la période \(`IndisponibiliteConflictException`\).
* Impossible s'il existe déjà une autre indisponibilité qui chevauche la période.

**Réponses**:

* `201`: indisponibilité créée (retourne `id` et les données)
* `409`: conflit avec un RDV ou une autre indisponibilité
* `400`: données invalides

---

#### 5\.5\.3 `DELETE /api/praticiens/{praticienId}/indisponibilites/{indispoId}`

**Description**: suppression d'une indisponibilité.

**Réponses**:

* `204` ou `200`: supprimée
* `404`: inexistante \(`IndisponibiliteNotFoundException`\)

---

### 5\.6 Rendez\-vous (`/api/rdvs`)

Toutes ces routes sont sous `/api/rdvs`.

#### 5\.6\.1 `GET /api/rdvs`

**Description**: liste les rendez\-vous de l'utilisateur connecté (patient ou praticien) avec possibilités de filtrage.

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('listRdvs')`.  
`AuthzService::canListUserRdvs` autorise les rôles `PATIENT` et `PRATICIEN`.

**Query parameters**(côté Action, transmis à `ServiceRdv::listRdvsFiltered`):

* `debut` \- date\-heure ISO\-8601 (optionnel)
* `fin` \- date\-heure ISO\-8601 (optionnel)
* `praticienId` \- filtrage par praticien (optionnel, surtout utile pour un patient)
* `pastOnly` \- `true` / `false`
    * `true`: uniquement l'historique
    * `false` ou absent: futur \+ passé

**Comportement**:

* pour un `PRATICIEN` :
    * lecture en base via `RdvRepositoryInterface::listForPraticienBetween(praticienId, debut, fin)`
    * si `praticienId` non fourni, c'est `user->ID` qui est utilisé
* pour un `PATIENT` :
    * lecture via `RdvRepositoryInterface::listForPatient(user->ID)`
    * filtrage en mémoire sur `debut`, `fin`, `praticienId`, `pastOnly`.

**Réponse 200**: tableau de `RendezVousDTO` avec liens associés:

```json
{
  "data": [
    {
      "id": "uuid",
      "praticienId": "uuid\-praticien",
      "patientId": "uuid\-patient",
      "patientEmail": "patient@example.com",
      "debut": "2025\-01\-01T08:00:00+00:00",
      "fin": "2025\-01\-01T08:30:00+00:00",
      "status": 1,
      "duree": 30,
      "motifVisite": "Consultation",
      "_links": {
        "self": {
          "href": "/api/rdvs/{id}"
        },
        "cancel": {
          "href": "/api/rdvs/{id}",
          "method": "DELETE"
        }
      }
    }
  ],
  "_links": {
    "self": {
      "href": "/api/rdvs?debut=...&fin=..."
    }
  }
}
```

---

#### 5\.6\.2 `GET /api/rdvs/{rdvId}`

**Description**: détail d'un RDV.

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('viewRdv')`  
Autorisé si:

* le praticien est le propriétaire du RDV, ou
* le patient est celui du RDV (`AuthzService::canAccessRdvDetails`).

**Paramètre**:

* `rdvId` \- id du RDV.

**Réponse 200**: `RendezVousDTO`.

**404**: RDV introuvable.

---

#### 5\.6\.3 `POST /api/rdvs`

**Description**: création d'un nouveau RDV.

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('createRdv')`.  
Uniquement `PATIENT`(`AuthzService::canCreateRdv`).

**Corps JSON** (`InputRendezVousDTO`):

```json
{
  "praticienId": "uuid\-praticien",
  "patientId": "uuid\-patient",
  "patientEmail": "patient@example.com",
  "debut": "2025\-01\-01T08:00:00+02:00",
  "dureeMinutes": 30,
  "motifVisite": "Consultation"
}
```

Validation dans `InputRendezVousDTO::fromArray` \+ `validate()`:

* `debut` doit être une date ISO\-8601 valide (convertie vers UTC en gardant l'heure locale).
* `dureeMinutes` \> 0.
* `motifVisite` non vide.
* `praticienId`, `patientId` non vides.
* `patientEmail` facultatif mais doit être un email valide si présent.

Règles métier dans `ServiceRdv::creerRdv`:

* le praticien doit exister (`PraticienNotFoundException` sinon),
* optionnellement, le motif doit être valide pour ce praticien (code commenté),
* calcul de la date de fin (`debut + dureeMinutes`),
* pas de RDV chevauchant sur ce créneau (`SlotConflictException`),
* pas d'indisponibilité sur ce créneau (`PraticienUnavailableException`),
* le praticien doit être disponible (`PraticienDetail::isAvailable`).

**Réponses**:

* `201`: RDV créé (retourne l'id ou le DTO selon votre Action)
* `404`: praticien inexistant
* `409`: créneau en conflit (RDV existant ou indisponibilité)
* `400`: données invalides

---

#### 5\.6\.4 `PATCH /api/rdvs/{rdvId}`

**Description**: modification partielle d'un RDV (ex. changement de statut, horaire, motif selon votre Action).

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('editRdv')`  
Uniquement par le praticien propriétaire du RDV (`AuthzService::canEditRdv`).

**Paramètre**:

* `rdvId` \- id du RDV.

**Corps JSON**: dépend de l'implémentation de `EditRdvAction` (par ex. nouveau `status`, nouvel horaire).

**Réponses**:

* `200`: RDV mis à jour
* `404`: introuvable
* `403`: non autorisé

---

#### 5\.6\.5 `DELETE /api/rdvs/{rdvId}`

**Description**: annulation / suppression d'un RDV.

**Auth**: `AuthnMiddleware` \+ `AuthzMiddleware('cancelRdv')`  
Accessible si l'utilisateur est le praticien ou le patient du RDV (`AuthzService::canCancelRdv` \=\>
`canAccessRdvDetails`).

**Effet**:

* `ServiceRdv::annulerRendezVous` :
    * charge le RDV,
    * déclenche `rdv->annuler()`,
    * supprime en base,
    * log l'annulation.

**Réponses**:

* `204` ou `200`: RDV annulé
* `404`: RDV inexistant.

---

## 6\. Collection Bruno pour tester l'API

Un dossier `Bruno/` est disponible à la racine du projet et contient **toutes les routes API déjà pré-configurées** pour faciliter les tests avec **Bruno** (client API).

La collection comprend :
* **auth/** : routes d'authentification (`POST-signin.bru`, `POST-signup.bru`)
* **praticiens/** : routes praticiens (`GET-list-praticiens.bru`, `GET-praticien.bru`, `GET-praticien-rdvs.bru`)
* **rdvs/** : routes rendez-vous (`GET-list-rdvs.bru`, `GET-rdv.bru`, `POST-rdv.bru`, `PATCH-rdv.bru`, `DELETE-rdv.bru`)
* **indisponibilites/** : routes indisponibilités (`GET-list-indisponibilites.bru`, `POST-create-indisponibilite.bru`, `DELETE-indisponibilite.bru`)
* **environments/** : environnement de développement (`DEV.bru`) avec l'URL de base configurée

Pour utiliser la collection :
1. Installez [Bruno](https://www.usebruno.com/)
2. Ouvrez la collection depuis le dossier `Bruno/`
3. Sélectionnez l'environnement `DEV`
4. Testez les routes directement !

---

## 7\. Format générique des réponses API

Toutes les réponses sont construites via `ApiResponseBuilder` et sont de la forme:

### Succès

```json
{
  "data": {...} || [...],
  "_links": {
    "self": {
      "href": "/api/..."
    },
    "...": {
      "href": "...",
      "method": "..."
    }
  }
}
```

### Erreur

```json
{
  "error": {
    "message": "Message lisible"
  }
}
```

En mode debug (si activé):

```json
{
  "error": {
    "type": "ExceptionType",
    "message": "Détail technique",
    "file": "...",
    "line": 123
  }
}
```

---

## 8\. Tableau de bord des fonctionnalités

### 8\.1 Fonctionnalités implémentées

| Fonctionnalité                                                                                    | Statut |
|---------------------------------------------------------------------------------------------------|-------:|
| 1. Lister les praticiens                                                                          |     ✔️ |
| 2. Détail d'un praticien (nom, prénom, spécialité, contacts, adresse, motifs, moyens de paiement) |     ✔️ |
| 3. Lister les créneaux de rdvs déjà occupés d'un praticien (période)                              |     ✔️ |
| 4. Consulter un rendez\-vous à partir de son identifiant                                          |     ✔️ |
| 5. Réserver un rendez\-vous auprès d'un praticien                                                 |     ✔️ |
| 6. Annuler un rendez\-vous (patient ou praticien)                                                 |     ✔️ |
| 7. Afficher l'agenda d'un praticien sur une période donnée (filtrage, patient, motif)             |     ✔️ |
| 8. S'authentifier (signin / signup)                                                               |     ✔️ |
| 9. Lister/rechercher un praticien par spécialité et/ou ville                                      |     ✔️ |
| 10. Gérer le cycle de vie des rendez\-vous (honoré, non honoré)                                   |     ✔️ |
| 11. Obtenir l'historique des consultations d'un patient                                           |     ✔️ |
| 12. S'inscrire en tant que patient                                                                |     ✔️ |
| 13. Gérer les indisponibilités temporaires d'un praticien (création, liste, suppression)          |     ✔️ |

---

### 8\.2 Répartition des réalisations (par membre du groupe)

| Membre       | Réalisations principales |
|--------------|--------------------------|
| Nathan OUDER | Tout                     |
