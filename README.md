# Toubilib - guide rapide

Ce depot demarre une architecture micro-services (gateway + auth + praticiens + rdv + patients + mailer + RabbitMQ).

## Prerequis
- Docker + Docker Compose

## Lancer le projet (pas a pas)
```bash
# 1) Config de base
cp .env.example .env

# 2) Build + demarrage des services
docker compose up -d --build

# 3) Generation / remplissage des bases
./scripts/db_seed.sh
```


## Arreter / nettoyer
```bash
# Arreter les services (garde les volumes)
docker compose down

# Reinitialiser completement (supprime les volumes)
docker compose down -v
```

## Points d'acces
- Gateway (API publique) : `http://localhost:7080/api`
- Mailcatcher : `http://localhost:1080`
- RabbitMQ UI : `http://localhost:15672` (user/pass: `toubi` / `toubi`)

## Documentation des routes
Voir `docs/ROUTES.md`.
