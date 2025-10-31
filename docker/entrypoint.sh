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

echo "[entrypoint] Executando migrations (com retry)"
retries=10
until php artisan migrate --force; do
  retries=$((retries-1))
  if [ $retries -le 0 ]; then
    echo "[entrypoint] Migrations falharam mesmo após tentativas; seguindo em frente." >&2
    break
  fi
  echo "[entrypoint] Banco indisponível. Tentando novamente em 5s... ($retries restantes)"
  sleep 5
done

echo "[entrypoint] Executando seeders (ignorar falhas)"
php artisan db:seed --force || true

echo "[entrypoint] Linkando storage"
php artisan storage:link || true

chown -R www-data:www-data storage bootstrap/cache || true

echo "[entrypoint] Iniciando Apache"
exec "$@"
