#!/usr/bin/env bash
set -euo pipefail

echo "Iniciando ambiente Docker (bash)..."

dc() { echo "> docker compose $*" >&2; docker compose "$@"; }

dc pull || echo "Aviso: docker compose pull falhou (seguindo)."

if [[ "${REBUILD:-}" == "1" ]]; then
  dc build --no-cache
else
  dc build
fi

dc up -d

echo "Configurando Laravel dentro do container..."
dc exec -T app sh -lc '
  set -e
  [ -f .env ] || cp .env.example .env
  php artisan key:generate --force
  php artisan migrate --force || true
  php artisan storage:link || true
  php artisan optimize:clear || true
'

echo "Executando build de assets (Node)..."
dc exec -T node sh -lc 'npm ci && npm run build' || echo "Aviso: build de assets falhou (verifique logs)."

echo "Ambiente no ar: http://localhost:8097"

