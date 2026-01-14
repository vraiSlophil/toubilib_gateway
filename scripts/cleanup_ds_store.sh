#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'USAGE'
Usage: cleanup_ds_store.sh [options] <source>

Supprime récursivement tous les fichiers nommés ".DS_Store" à partir d'une source.

Options:
  -n, --dry-run   Affiche les fichiers qui seraient supprimés, sans supprimer.
  -y, --yes       Ne demande pas de confirmation.
  -h, --help      Affiche cette aide.

Exemples:
  ./scripts/cleanup_ds_store.sh .
  ./scripts/cleanup_ds_store.sh --dry-run /chemin/vers/projet
  ./scripts/cleanup_ds_store.sh --yes /chemin/vers/projet
USAGE
}

dry_run=false
assume_yes=false

if [[ $# -eq 0 ]]; then
  usage
  exit 2
fi

source_path=""
while [[ $# -gt 0 ]]; do
  case "$1" in
    -n|--dry-run)
      dry_run=true
      shift
      ;;
    -y|--yes)
      assume_yes=true
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    --)
      shift
      break
      ;;
    -*)
      echo "Option inconnue: $1" >&2
      usage >&2
      exit 2
      ;;
    *)
      if [[ -n "$source_path" ]]; then
        echo "Trop d'arguments. Source déjà définie: $source_path" >&2
        usage >&2
        exit 2
      fi
      source_path="$1"
      shift
      ;;
  esac
done

if [[ -z "$source_path" ]]; then
  echo "Source manquante." >&2
  usage >&2
  exit 2
fi

if [[ ! -e "$source_path" ]]; then
  echo "Source introuvable: $source_path" >&2
  exit 1
fi

# Normalise en chemin absolu quand possible (sans dépendre de readlink -f sur macOS).
if command -v realpath >/dev/null 2>&1; then
  source_path_abs="$(realpath "$source_path")"
else
  # Fallback: conserve tel quel.
  source_path_abs="$source_path"
fi

mapfile -d '' files < <(find "$source_path" -type f -name '.DS_Store' -print0 2>/dev/null || true)

if [[ ${#files[@]} -eq 0 ]]; then
  echo "Aucun fichier .DS_Store trouvé sous: $source_path_abs"
  exit 0
fi

echo "${#files[@]} fichier(s) .DS_Store trouvé(s) sous: $source_path_abs"

if $dry_run; then
  printf '%s\n' "--- DRY RUN: aucun fichier ne sera supprimé ---"
  printf '%s\n' "Fichiers:" 
  printf ' - %s\n' "${files[@]}"
  exit 0
fi

if ! $assume_yes; then
  echo "Cette opération va supprimer définitivement ces fichiers:" 
  printf ' - %s\n' "${files[@]}"
  read -r -p "Continuer ? [y/N] " answer
  case "${answer}" in
    y|Y|yes|YES|Oui|OUI|o|O)
      ;;
    *)
      echo "Annulé."
      exit 0
      ;;
  esac
fi

# Suppression sécurisée (gère les espaces, etc.)
find "$source_path" -type f -name '.DS_Store' -print0 2>/dev/null | xargs -0 -r rm -f

echo "Suppression terminée." 
