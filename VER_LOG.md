# 📋 Ver Logs no VPS

## Ver últimos erros do Laravel

```bash
# Ver últimas 50 linhas do log
tail -n 50 /var/www/grafica/storage/logs/laravel.log

# Ver últimas 100 linhas
tail -n 100 /var/www/grafica/storage/logs/laravel.log

# Ver log em tempo real (seguir atualizações)
tail -f /var/www/grafica/storage/logs/laravel.log

# Ver apenas erros (linhas com ERROR)
grep "ERROR" /var/www/grafica/storage/logs/laravel.log | tail -n 50

# Ver últimos erros com contexto
tail -n 200 /var/www/grafica/storage/logs/laravel.log | grep -A 10 -B 5 "ERROR"

# Ver erro específico do admin
grep -i "admin" /var/www/grafica/storage/logs/laravel.log | tail -n 50

# Ver todo o log (cuidado, pode ser muito grande)
cat /var/www/grafica/storage/logs/laravel.log

# Ver log e salvar em arquivo
tail -n 100 /var/www/grafica/storage/logs/laravel.log > erro_log.txt
cat erro_log.txt
```

## Ver logs do Nginx

```bash
# Erros do Nginx
sudo tail -n 50 /var/log/nginx/error.log

# Acesso do Nginx
sudo tail -n 50 /var/log/nginx/access.log
```

## Ver logs do PHP-FPM

```bash
# Logs do PHP
sudo tail -n 50 /var/log/php8.1-fpm.log
```

## Comando mais rápido (últimos 50 erros)

```bash
tail -n 50 /var/www/grafica/storage/logs/laravel.log | grep -A 5 "ERROR"
```

