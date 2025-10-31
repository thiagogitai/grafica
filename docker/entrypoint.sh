#!/usr/bin/env bash
set -e

cd /var/www/html

if [ ! -f composer.json ]; then
  echo "composer.json não encontrado em /var/www/html" >&2
  exec "$@"
fi

echo "[entrypoint] Instalando dependências PHP (composer)"
composer install --no-interaction --prefer-dist --no-progress

if [ ! -f .env ]; then
  echo "[entrypoint] Copiando .env.example para .env"
  cp .env.example .env || true
fi

php artisan key:generate --force || true

echo "[entrypoint] Executando migrations"
php artisan migrate --force || true

echo "[entrypoint] Executando seeders"
php artisan db:seed --force || true

echo "[entrypoint] Linkando storage"
php artisan storage:link || true

chown -R www-data:www-data storage bootstrap/cache || true

echo "[entrypoint] Iniciando Apache"
exec "$@"

