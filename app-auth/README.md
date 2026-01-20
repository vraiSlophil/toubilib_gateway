# app-auth (microservice d’authentification)

Ce microservice expose uniquement les routes d’authentification de Toubilib.

## Routes

Toutes les routes sont sous le préfixe `/api` :

- `POST /api/auth/signup`
- `POST /api/auth/signin`
- `POST /api/auth/refresh`

## Démarrage (via docker compose)

Ce service est lancé par `docker-compose.yml` sous le nom `api.auth` et utilise la base Postgres `toubiauth.db`.

## Notes

- La gateway route `/api/auth/*` vers ce microservice.
- Le JWT (`JWT_SECRET`, `JWT_ALGORITHM`, expirations) doit être identique à celui utilisé par la gateway et les autres services.

