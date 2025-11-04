# 🔄 Atualizar Sistema no VPS

## Comandos para Atualizar no VPS

### 1. Atualizar Código
```bash
cd /var/www/grafica  # ou o caminho do seu projeto
git pull origin main
```

### 2. Atualizar Dependências
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Rodar Migrations (se houver)
```bash
php artisan migrate --force
```

### 4. Limpar e Recriar Cache
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 5. Verificar Permissões
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Reiniciar Serviços (se necessário)
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
```

### 7. Verificar se Python/Selenium está funcionando
```bash
python3 --version
python3 -c "from selenium import webdriver; print('Selenium OK')"
```

## ✅ Pronto!

Após esses comandos, o sistema está atualizado e funcionando.

