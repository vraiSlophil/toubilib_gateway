# Documentation des routes

## Conventions
- Base URL (gateway) : `http://localhost:7080/api`
- Header pour les routes protegees : `Authorization: Bearer <access_token>`
- Dates : ISO-8601 (ex: `2026-01-27T14:30:00+00:00`)
- Les reponses sont au format JSON (style JSON:API: `data`, `links`, `errors`).

---

## Auth (via gateway)

### POST /auth/signup
Cree un compte.

Body JSON :
```json
{
  "email": "alice.dupont@example.com",
  "password": "S3cretPwd!",
  "role": 1
}
```

### POST /auth/signin
Authentifie un utilisateur et retourne un access token + refresh token.

Body JSON :
```json
{
  "email": "alice.dupont@example.com",
  "password": "S3cretPwd!"
}
```

### POST /auth/refresh
Renouvelle les tokens.

Body JSON :
```json
{
  "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

---

## RDV (via gateway)

### GET /rdvs
Liste les rendez-vous du user courant.

Query params (optionnels) :
- `debut` (ISO-8601)
- `fin` (ISO-8601)
- `praticienId` (string)
- `history` (n'importe quelle valeur -> ne renvoyer que le passe)

Auth : requis (patient ou praticien).

### POST /rdvs
Cree un rendez-vous.

Body JSON :
```json
{
  "praticienId": "4305f5e9-be5a-4ccf-8792-7e07d7017363",
  "patientId": "8c6d6f3e-9d8a-4c0a-bf93-0d3f7f2e9a10",
  "debut": "2026-01-27T14:30:00+00:00",
  "dureeMinutes": 30,
  "motifVisite": "consultation"
}
```

Notes :
- Si l'utilisateur est patient, `patientId`/`patientEmail` sont forces depuis le token.
- Si l'utilisateur est praticien, `praticienId` est force a son ID et il ne peut pas creer pour un autre praticien.

### GET /rdvs/{rdvId}
Recupere un rendez-vous (patient ou praticien concerne).

Auth : requis.

### PATCH /rdvs/{rdvId}
Met a jour le statut d'un rendez-vous.

Body JSON :
```json
{
  "status": true
}
```

Auth : requis (praticien concerne).

### DELETE /rdvs/{rdvId}
Annule un rendez-vous (futur uniquement).

Auth : requis.

---

## Praticiens (via gateway)

### GET /praticiens
Liste / recherche des praticiens.

Query params :
- `email` (string, recherche exacte par email)
- ou `specialiteId` (int) et/ou `ville` (string)

### POST /praticiens
Cree un praticien.

Auth : requis (praticien).

Body JSON (InputPraticienDTO) :
```json
{
  "nom": "Martin",
  "prenom": "Claire",
  "ville": "Nancy",
  "email": "claire.martin@cabinet.fr",
  "telephone": "0380123456",
  "specialiteId": 2,
  "rppsId": "12345678901",
  "titre": "Dr.",
  "accepteNouveauPatient": true,
  "estOrganisation": false,
  "structureId": "e65145bb-ce57-4320-b0a8-6c0ba06def6d"
}
```

### GET /praticiens/{praticienId}
Recupere un praticien.

### PUT /praticiens/{praticienId}
Met a jour un praticien.

Auth : requis.
Body : meme format que POST /praticiens.

### DELETE /praticiens/{praticienId}
Supprime un praticien.

Auth : requis.

### GET /praticiens/{praticienId}/rdvs
Liste les creneaux reserves pour un praticien.

Auth : requis (praticien concerne).

Query params (requis) :
- `debut` (ISO-8601)
- `fin` (ISO-8601)

---

## Indisponibilites (via gateway)

### GET /praticiens/{praticienId}/indisponibilites
Liste les indisponibilites d'un praticien.

Auth : requis (praticien concerne).

### POST /praticiens/{praticienId}/indisponibilites
Cree une indisponibilite.

Auth : requis (praticien concerne).

Body JSON :
```json
{
  "debut": "2026-01-28T09:00:00+00:00",
  "fin": "2026-01-28T12:00:00+00:00",
  "motif": "conge"
}
```

### GET /praticiens/{praticienId}/indisponibilites/{indispoId}
Recupere une indisponibilite.

Auth : requis.

### PUT /praticiens/{praticienId}/indisponibilites/{indispoId}
Met a jour une indisponibilite.

Auth : requis.
Body : meme format que POST.

### DELETE /praticiens/{praticienId}/indisponibilites/{indispoId}
Supprime une indisponibilite.

Auth : requis.

---

## Patients (via gateway)

### GET /patients
Liste les patients.

Auth : requis.

### POST /patients
Cree le profil patient du user courant.

Auth : requis (role patient).

Body JSON (InputPatientDTO) :
```json
{
  "nom": "Dupont",
  "prenom": "Alice",
  "telephone": "0601020304",
  "dateNaissance": "1990-04-15",
  "adresse": "12 rue des Lilas",
  "codePostal": "54000",
  "ville": "Nancy",
  "email": "alice.dupont@example.com"
}
```

### GET /patients/{patientId}
Recupere un patient.

Auth : requis.

### PUT /patients/{patientId}
Met a jour un patient.

Auth : requis.
Body : meme format que POST /patients.

### DELETE /patients/{patientId}
Supprime un patient.

Auth : requis.

---

## Routes internes (microservices)

### Auth service uniquement
- **POST /api/tokens/validate**
  - Header: `Authorization: Bearer <token>` **ou** body :
    ```json
    {
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
    }
    ```
  - Description: valide un JWT et renvoie le profil decode.
  - Base URL interne (docker network): `http://api.auth:80/api`

Note: les microservices ne sont pas exposes sur l'hote par defaut. Passez par la gateway ou mappez un port si besoin.
