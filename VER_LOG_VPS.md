# 📋 Ver Logs no VPS - Caminho Correto

## Seu projeto está em: `/www/wwwroot/grafica`

## Comandos Corretos:

```bash
# Ver últimos 50 erros
tail -n 50 /www/wwwroot/grafica/storage/logs/laravel.log

# Ver últimos 100 erros
tail -n 100 /www/wwwroot/grafica/storage/logs/laravel.log

# Ver em tempo real (melhor opção)
tail -f /www/wwwroot/grafica/storage/logs/laravel.log

# Ver apenas linhas com ERROR
grep "ERROR" /www/wwwroot/grafica/storage/logs/laravel.log | tail -n 50

# Ver erro com contexto
tail -n 200 /www/wwwroot/grafica/storage/logs/laravel.log | grep -A 10 -B 5 "ERROR"

# Ver últimos erros (comando mais rápido)
cd /www/wwwroot/grafica
tail -n 100 storage/logs/laravel.log | grep -A 10 "ERROR"
```

## Se o arquivo não existir, criar:

```bash
cd /www/wwwroot/grafica
mkdir -p storage/logs
touch storage/logs/laravel.log
chmod -R 775 storage
chown -R www-data:www-data storage
```

## Ver logs do Nginx/Apache:

```bash
# Nginx
sudo tail -n 50 /var/log/nginx/error.log

# Apache
sudo tail -n 50 /var/log/apache2/error.log
# ou
sudo tail -n 50 /etc/httpd/logs/error_log
```

## Verificar se o log existe:

```bash
ls -la /www/wwwroot/grafica/storage/logs/
```

