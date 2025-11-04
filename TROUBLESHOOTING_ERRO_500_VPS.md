# 🔧 Troubleshooting - Erro 500 no VPS Laravel

## ✅ Correção Aplicada

O problema principal foi corrigido: o `LivroPriceController` agora detecta automaticamente se está rodando no Windows ou Linux e usa o comando Python correto (`python3` no Linux/VPS).

## 📋 Checklist de Verificação no VPS

### 1. Verificar Logs do Laravel

```bash
# Conectar ao VPS
ssh seu-usuario@seu-vps

# Navegar até o projeto
cd /www/wwwroot/grafica

# Ver últimos erros (mais importante!)
tail -n 100 storage/logs/laravel.log | grep -A 10 "ERROR"

# Ver em tempo real
tail -f storage/logs/laravel.log
```

### 2. Verificar Permissões de Arquivos

```bash
cd /www/wwwroot/grafica

# Corrigir permissões de storage e cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Verificar se o diretório de logs existe
mkdir -p storage/logs
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

### 3. Verificar Arquivo .env

```bash
cd /www/wwwroot/grafica

# Verificar se .env existe
ls -la .env

# Se não existir, copiar do exemplo
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Verificar configurações importantes
cat .env | grep -E "APP_DEBUG|APP_ENV|APP_URL|DB_"
```

**Importante no .env:**
- `APP_DEBUG=false` (em produção)
- `APP_ENV=production` (em produção)
- `APP_URL` deve estar correto (ex: `https://seusite.com`)
- Configurações de banco de dados corretas

### 4. Limpar e Recriar Cache

```bash
cd /www/wwwroot/grafica

# Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recriar cache (em produção)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5. Verificar Link do Storage

```bash
cd /www/wwwroot/grafica

# Verificar se o link existe
ls -la public/storage

# Se não existir, criar
php artisan storage:link
```

### 6. Verificar Python e Dependências

```bash
# Verificar se Python 3 está instalado
python3 --version

# Verificar se o script existe
ls -la /www/wwwroot/grafica/scrapper/scrape_tempo_real.py

# Testar se Python consegue executar o script
cd /www/wwwroot/grafica
python3 scrapper/scrape_tempo_real.py '{"opcoes":{"quantity":50},"quantidade":50}'

# Verificar se Selenium está instalado
python3 -c "import selenium; print('Selenium OK')"
```

### 7. Verificar Logs do Nginx/Apache

```bash
# Nginx
sudo tail -n 50 /var/log/nginx/error.log

# Apache
sudo tail -n 50 /var/log/apache2/error.log
# ou
sudo tail -n 50 /etc/httpd/logs/error_log
```

### 8. Verificar PHP e Extensões

```bash
# Verificar versão do PHP
php -v

# Verificar extensões necessárias
php -m | grep -E "pdo|mysql|mbstring|xml|curl|zip|gd"

# Verificar limites do PHP
php -i | grep -E "memory_limit|max_execution_time|upload_max_filesize"
```

### 9. Verificar Banco de Dados

```bash
cd /www/wwwroot/grafica

# Testar conexão
php artisan tinker
# No tinker: DB::connection()->getPdo();

# Verificar migrations
php artisan migrate:status

# Executar migrations pendentes (se necessário)
php artisan migrate --force
```

### 10. Verificar Permissões do Usuário do Servidor Web

```bash
# Verificar qual usuário está rodando o servidor web
ps aux | grep -E "nginx|apache|httpd"

# Verificar se o usuário pode executar Python
sudo -u www-data python3 --version

# Se não funcionar, pode precisar dar permissão
sudo chmod +x /usr/bin/python3
```

## 🚨 Problemas Comuns e Soluções

### Problema: "python não é reconhecido"
**Solução:** ✅ Já corrigido no código - agora usa `python3` automaticamente no Linux

### Problema: "Permission denied" em storage/logs
```bash
chmod -R 775 storage
chown -R www-data:www-data storage
```

### Problema: "Class not found" ou "Service provider not found"
```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan config:cache
```

### Problema: "SQLSTATE[HY000] [2002] Connection refused"
- Verificar se o banco de dados está rodando
- Verificar configurações de DB_HOST, DB_PORT no .env
- Verificar firewall/portas

### Problema: "The stream or file could not be opened"
```bash
mkdir -p storage/logs
chmod -R 775 storage/logs
chown -R www-data:www-data storage/logs
```

## 📝 Comandos Rápidos de Diagnóstico

```bash
# Script completo de verificação
cd /www/wwwroot/grafica

echo "=== Verificando .env ==="
[ -f .env ] && echo "✓ .env existe" || echo "✗ .env NÃO existe"

echo "=== Verificando permissões ==="
ls -la storage/logs/laravel.log 2>/dev/null && echo "✓ Log existe" || echo "✗ Log NÃO existe"

echo "=== Verificando Python ==="
python3 --version && echo "✓ Python OK" || echo "✗ Python NÃO encontrado"

echo "=== Verificando storage link ==="
[ -L public/storage ] && echo "✓ Link existe" || echo "✗ Link NÃO existe"

echo "=== Últimos erros ==="
tail -n 20 storage/logs/laravel.log 2>/dev/null | grep ERROR || echo "Nenhum erro recente"
```

## 🔍 Verificar Erro Específico

Se ainda tiver erro 500, execute:

```bash
cd /www/wwwroot/grafica

# Ver último erro completo
tail -n 200 storage/logs/laravel.log | grep -A 20 "ERROR"

# Ver stack trace completo
tail -n 500 storage/logs/laravel.log | tail -n 100
```

## 📞 Próximos Passos

1. Execute o checklist acima no seu VPS
2. Copie o último erro completo do log
3. Verifique se o problema foi resolvido após as correções
4. Se ainda persistir, compartilhe o erro específico do log

