#!/usr/bin/env bash
set -euo pipefail

# Hydrate les bases Postgres de toubilib (schema puis data) via docker compose.
# Usage:
#   ./scripts/db_seed.sh
# Options:
#   --no-data   n'applique que les *.schema.sql
#   --no-schema n'applique que les *.data.sql

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SQL_DIR="$ROOT_DIR/sql"
ENV_FILE="$ROOT_DIR/app/config/.env"

APPLY_SCHEMA=1
APPLY_DATA=1

while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-data) APPLY_DATA=0; shift ;;
    --no-schema) APPLY_SCHEMA=0; shift ;;
    -h|--help)
      echo "Usage: $0 [--no-data] [--no-schema]";
      exit 0
      ;;
    *)
      echo "Option inconnue: $1" >&2
      exit 2
      ;;
  esac
done

if [[ ! -d "$SQL_DIR" ]]; then
  echo "Dossier SQL introuvable: $SQL_DIR" >&2
  exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Fichier .env introuvable: $ENV_FILE" >&2
  exit 1
fi

# Charge un .env simple (KEY=VALUE), ignore commentaires/lignes vides.
load_env() {
  local file="$1"
  while IFS='=' read -r key value; do
    [[ -z "${key:-}" ]] && continue
    [[ "$key" =~ ^# ]] && continue
    key="$(echo "$key" | xargs)"
    value="$(echo "${value:-}" | xargs)"
    export "$key"="$value"
  done < <(grep -vE '^\s*#' "$file" | grep -vE '^\s*$')
}

load_env "$ENV_FILE"

wait_for_pg() {
  local service="$1"
  local user="$2"
  local db="$3"

  echo "⏳ Attente Postgres: $service (db=$db user=$user)" >&2
  for i in {1..40}; do
    if docker compose exec -T "$service" bash -lc "pg_isready -U '$user' -d '$db' >/dev/null 2>&1"; then
      echo "✅ Postgres OK: $service" >&2
      return 0
    fi
    sleep 1
  done

  echo "❌ Postgres non prêt: $service" >&2
  return 1
}

apply_sql_file() {
  local service="$1"
  local user="$2"
  local db="$3"
  local sql_file_in_container="$4"

  echo "➡️  $service: $sql_file_in_container" >&2
  docker compose exec -T "$service" bash -lc "psql -v ON_ERROR_STOP=1 -U '$user' -d '$db' -f '$sql_file_in_container'" >/dev/null
}

seed_one() {
  local label="$1"          # ex: toubiprat
  local service="$2"        # ex: toubiprati.db
  local user="$3"
  local db="$4"

  local schema_file_host="$SQL_DIR/${label}.schema.sql"
  local data_file_host="$SQL_DIR/${label}.data.sql"

  if [[ $APPLY_SCHEMA -eq 1 && ! -f "$schema_file_host" ]]; then
    echo "⚠️  Schema manquant: $schema_file_host (skip)" >&2
    APPLY_SCHEMA_FOR_THIS=0
  fi

  if [[ $APPLY_DATA -eq 1 && ! -f "$data_file_host" ]]; then
    echo "⚠️  Data manquante: $data_file_host (skip)" >&2
    APPLY_DATA_FOR_THIS=0
  fi

  wait_for_pg "$service" "$user" "$db"

  if [[ $APPLY_SCHEMA -eq 1 ]]; then
    apply_sql_file "$service" "$user" "$db" "/var/sql/${label}.schema.sql"
  fi

  if [[ $APPLY_DATA -eq 1 ]]; then
    apply_sql_file "$service" "$user" "$db" "/var/sql/${label}.data.sql"
  fi

  echo "✅ Hydratation OK: $label" >&2
}

# Mapping fichiers sql -> services docker compose
seed_one "toubiprat" "toubiprati.db" "${PRAT_USERNAME}" "${PRAT_DATABASE}"
seed_one "toubiauth" "toubiauth.db" "${AUTH_USERNAME}" "${AUTH_DATABASE}"
seed_one "toubirdv"  "toubirdv.db"  "${RDV_USERNAME}"  "${RDV_DATABASE}"
seed_one "toubipat"  "toubipat.db"  "${PAT_USERNAME}"  "${PAT_DATABASE}"

echo "🎉 Terminé." >&2

