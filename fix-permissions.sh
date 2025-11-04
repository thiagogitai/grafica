#!/bin/bash
# Script para corrigir permissões do Laravel no VPS

cd /www/wwwroot/grafica

echo "Corrigindo permissões..."

# Criar diretórios se não existirem
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Dar permissões de escrita
chmod -R 775 storage bootstrap/cache

# Mudar proprietário para www (usuário do PHP-FPM)
chown -R www:www storage bootstrap/cache

# Verificar
echo "Verificando permissões..."
ls -la storage/framework/views | head -5
ls -la bootstrap/cache | head -5

echo "✓ Permissões corrigidas!"

