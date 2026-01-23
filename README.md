```bash
cp .env.example .env

docker compose run --rm --remove-orphans mailer.toubilib composer install

docker compose up -d --build

./scripts/db_seed.sh
```