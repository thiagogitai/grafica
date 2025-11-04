# 🔍 Diagnóstico do Erro 500 no /admin

## Comandos para Diagnosticar no VPS

### 1. Verificar Logs
```bash
tail -f /var/www/grafica/storage/logs/laravel.log
```

### 2. Verificar se usuário admin existe
```bash
cd /var/www/grafica
php artisan tinker
```
No tinker:
```php
$user = App\Models\User::where('email', 'admin@todahgrafica.com.br')->first();
if ($user) {
    echo "Usuario encontrado\n";
    echo "Admin: " . ($user->is_admin ? 'Sim' : 'Não') . "\n";
} else {
    echo "Usuario nao encontrado\n";
}
exit
```

### 3. Criar/Atualizar usuário admin
```bash
php artisan admin:create admin@todahgrafica.com.br admin123
```

### 4. Verificar se tabelas existem
```bash
php artisan tinker
```
No tinker:
```php
try {
    $orders = App\Models\Order::count();
    $products = App\Models\Product::count();
    echo "Orders: $orders\n";
    echo "Products: $products\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
exit
```

### 5. Verificar se view existe
```bash
ls -la /var/www/grafica/resources/views/admin/dashboard.blade.php
```

### 6. Limpar cache e otimizar
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Verificar permissões
```bash
sudo chown -R www-data:www-data /var/www/grafica
sudo chmod -R 755 /var/www/grafica
sudo chmod -R 775 /var/www/grafica/storage
sudo chmod -R 775 /var/www/grafica/bootstrap/cache
```

### 8. Testar rota diretamente
```bash
php artisan route:list | grep admin
```

### 9. Verificar se APP_DEBUG está ativo (para ver erro completo)
No .env:
```
APP_DEBUG=true
```

### 10. Verificar se banco de dados está conectado
```bash
php artisan tinker
```
No tinker:
```php
DB::connection()->getPdo();
echo "Conexao OK\n";
exit
```

## Possíveis Causas

1. **Usuário não é admin** - Criar com `php artisan admin:create`
2. **Tabelas não existem** - Rodar `php artisan migrate --force`
3. **View não existe** - Verificar se `resources/views/admin/dashboard.blade.php` existe
4. **Erro no Product::templateOptions()** - Verificar se método existe
5. **Cache corrompido** - Limpar todos os caches
6. **Permissões incorretas** - Corrigir permissões do storage

