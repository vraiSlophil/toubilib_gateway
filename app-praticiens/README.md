# app-praticiens

Microservice **praticiens** extrait de Toubilib (TD 2.1 – Exercice 3).

## Ce que ce microservice expose

- `GET /api/praticiens`
- `GET /api/praticiens/{praticienId}`
- `GET /api/praticiens/{praticienId}/rdvs`
- `GET|POST|DELETE /api/praticiens/{praticienId}/indisponibilites...`

Toutes les autres routes du monolithe (auth, rdvs globaux, etc.) ne sont plus exposées ici.

## Démarrage

Le service est démarré via docker-compose : `api.praticiens.toubilib`.

Port par défaut : `6180` (host) → `80` (conteneur).

## Notes

- Ce microservice réutilise la base Postgres praticiens existante (`toubiprati.db`).
- La gateway route automatiquement les requêtes `/api/praticiens...` vers ce microservice.

