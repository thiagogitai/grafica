# Comandos para executar no VPS

## 1. Atualizar código do Git
```bash
cd /www/wwwroot/grafica
git pull
```

## 2. Verificar se os arquivos foram atualizados
```bash
git status
ls -la app/Http/Controllers/ApiPricingProxyController.php
```

## 3. Limpar cache do Laravel (opcional, mas recomendado)
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 4. Verificar logs (para ver se há erros)
```bash
tail -f storage/logs/laravel.log
```

## 5. Testar a API localmente no VPS (opcional)
```bash
# Criar script de teste rápido
cat > /tmp/testar_api_vps.php << 'EOF'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProductPriceController;

$opcoes = [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 25,
    'formato_miolo_paginas' => '118x175mm',
    'papel_capa' => 'Couche Brilho 150gr',
    'cores_capa' => '4 cores FxV',
    'orelha_capa' => 'COM Orelha de 8cm',
    'acabamento_capa' => 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
    'papel_miolo' => 'Pólen Natural 80g',
    'cores_miolo' => '1 cor frente e verso PRETO',
    'miolo_sangrado' => 'SIM',
    'quantidade_paginas_miolo' => 'Miolo 12 páginas',
    'acabamento_miolo' => 'Dobrado',
    'acabamento_livro' => 'Costurado',
    'guardas_livro' => 'offset 180g - sem impressão',
    'extras' => 'Shrink Individual',
    'frete' => 'Cliente Retira',
    'verificacao_arquivo' => 'Digital On-Line - via Web-Approval ou PDF',
    'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
];

$request = new Request($opcoes);
$controller = new ProductPriceController();
$response = $controller->validatePrice($request);
$data = json_decode($response->getContent(), true);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Resposta: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
EOF

cd /www/wwwroot/grafica
php /tmp/testar_api_vps.php
```

## 6. Verificar permissões (se necessário)
```bash
chmod -R 775 storage bootstrap/cache
chown -R www:www storage bootstrap/cache
```

## 7. Reiniciar PHP-FPM (se necessário)
```bash
# Para PHP 8.2 (ajuste conforme sua versão)
/etc/init.d/php-fpm-82 restart
# Ou
systemctl restart php-fpm-82
```

