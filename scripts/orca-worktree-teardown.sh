#!/usr/bin/env bash
# Encerra apenas a stack deste worktree (projeto definido em .env.orca).
# --purge: remove também o .env.orca (a próxima subida escolhe nova porta/projeto).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if [ ! -f .env.orca ]; then
    echo "Sem .env.orca: nada a encerrar neste worktree."
    exit 0
fi

set -a; source .env.orca; set +a
docker compose -p "$COMPOSE_PROJECT_NAME" -f docker-compose.orca.yml --env-file .env.orca down --remove-orphans
echo "Stack $COMPOSE_PROJECT_NAME encerrada."

[ "${1:-}" = "--purge" ] && rm -f .env.orca && echo ".env.orca removido."
exit 0
