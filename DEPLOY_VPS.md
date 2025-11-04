# 🚀 Guia Completo de Deploy no VPS

## 📋 Passo a Passo para Deploy no VPS

### 1. Conectar no VPS
```bash
ssh usuario@seu-vps-ip
```

### 2. Instalar Dependências Básicas
```bash
# Atualizar sistema
sudo apt-get update
sudo apt-get upgrade -y

# Instalar PHP e extensões
sudo apt-get install -y php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-bcmath

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar Git
sudo apt-get install -y git

# Instalar Nginx (ou Apache)
sudo apt-get install -y nginx
```

### 3. Instalar Python e Selenium
```bash
# Instalar Python 3
sudo apt-get install -y python3 python3-pip

# Instalar Selenium
pip3 install selenium

# Instalar Chrome e ChromeDriver
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo dpkg -i google-chrome-stable_current_amd64.deb
sudo apt-get install -f -y

# Instalar ChromeDriver
CHROMEDRIVER_VERSION=$(curl -sS chromedriver.storage.googleapis.com/LATEST_RELEASE)
wget https://chromedriver.storage.googleapis.com/$CHROMEDRIVER_VERSION/chromedriver_linux64.zip
unzip chromedriver_linux64.zip
sudo mv chromedriver /usr/local/bin/
sudo chmod +x /usr/local/bin/chromedriver
```

### 4. Clonar Repositório
```bash
cd /var/www
sudo git clone https://github.com/thiagogitai/grafica.git
sudo chown -R www-data:www-data grafica
cd grafica
```

### 5. Instalar Dependências do Laravel
```bash
composer install --no-dev --optimize-autoloader
```

### 6. Configurar Ambiente
```bash
# Copiar .env
cp .env.example .env

# Gerar chave
php artisan key:generate

# Editar .env com suas configurações
nano .env
```

**Configurações importantes no .env:**
```env
APP_NAME="Gráfica Todah"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com.br

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grafica
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 7. Configurar Banco de Dados
```bash
# Instalar MySQL
sudo apt-get install -y mysql-server

# Criar banco
sudo mysql -e "CREATE DATABASE grafica CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'grafica_user'@'localhost' IDENTIFIED BY 'senha_forte';"
sudo mysql -e "GRANT ALL PRIVILEGES ON grafica.* TO 'grafica_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Rodar migrations
php artisan migrate --force

# Criar usuário admin
php artisan admin:create admin@todahgrafica.com.br admin123
```

### 8. Configurar Permissões
```bash
cd /var/www/grafica
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 9. Configurar Nginx
```bash
sudo nano /etc/nginx/sites-available/grafica
```

**Conteúdo do arquivo:**
```nginx
server {
    listen 80;
    server_name seu-dominio.com.br;
    root /var/www/grafica/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Ativar site
sudo ln -s /etc/nginx/sites-available/grafica /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 10. Configurar SSL (HTTPS) - Opcional mas Recomendado
```bash
sudo apt-get install -y certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com.br
```

### 11. Otimizar Laravel
```bash
cd /var/www/grafica
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 12. Configurar Supervisor (Para Processos em Background)
```bash
sudo apt-get install -y supervisor
sudo nano /etc/supervisor/conf.d/grafica-worker.conf
```

**Conteúdo:**
```ini
[program:grafica-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/grafica/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/grafica/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start grafica-worker:*
```

### 13. Testar
```bash
# Testar se Python funciona
python3 --version
python3 -c "from selenium import webdriver; print('Selenium OK')"
chromedriver --version

# Testar se Laravel funciona
php artisan route:list
php artisan tinker --execute="echo 'Laravel OK';"
```

### 14. Verificar Logs
```bash
# Logs do Laravel
tail -f /var/www/grafica/storage/logs/laravel.log

# Logs do Nginx
sudo tail -f /var/log/nginx/error.log

# Logs do PHP-FPM
sudo tail -f /var/log/php8.1-fpm.log
```

## 🔧 Comandos Úteis Após Deploy

### Atualizar Código
```bash
cd /var/www/grafica
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Limpar Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

### Reiniciar Serviços
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo supervisorctl restart grafica-worker:*
```

## ⚠️ Troubleshooting

### Se Python não funcionar
```bash
# Verificar caminho
which python3
which chromedriver

# Testar script manualmente
cd /var/www/grafica/scrapper
python3 scrape_tempo_real.py '{"opcoes":{"quantity":50},"quantidade":50}'
```

### Se permissões estiverem erradas
```bash
sudo chown -R www-data:www-data /var/www/grafica
sudo chmod -R 755 /var/www/grafica
sudo chmod -R 775 /var/www/grafica/storage
sudo chmod -R 775 /var/www/grafica/bootstrap/cache
```

### Se ChromeDriver não funcionar
```bash
# Verificar versão do Chrome
google-chrome --version

# Baixar ChromeDriver compatível
# Ver: https://chromedriver.chromium.org/downloads
```

## 📝 Checklist Final

- [ ] PHP 8.1+ instalado
- [ ] Composer instalado
- [ ] Python 3 instalado
- [ ] Selenium instalado
- [ ] Chrome e ChromeDriver instalados
- [ ] Repositório clonado
- [ ] .env configurado
- [ ] Banco de dados criado e migrado
- [ ] Permissões configuradas
- [ ] Nginx configurado
- [ ] SSL configurado (opcional)
- [ ] Laravel otimizado
- [ ] Supervisor configurado (opcional)
- [ ] Testado manualmente

## 🎯 Repositório

**URL:** `https://github.com/thiagogitai/grafica.git`

**Branch:** `main`

