<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Http\Request;
use App\Services\ProductConfig;
use App\Http\Controllers\ProductPriceController;

$config = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$options = ['product_slug' => 'impressao-de-livro'];
foreach (($config['options'] ?? []) as $opt) {
    $name = $opt['name'] ?? null;
    if (!$name) continue;
    if ($name === 'quantity') {
        $options['quantity'] = (int) ($opt['default'] ?? 50);
        continue;
    }
    $choices = $opt['choices'] ?? [];
    $value = $opt['default'] ?? ($choices[0]['value'] ?? $choices[0]['label'] ?? null);
    if ($value !== null) {
        $options[$name] = $value;
    }
}
$request = Request::create('/api/product/validate-price', 'POST', $options);
$response = app(ProductPriceController::class)->validatePrice($request);
file_put_contents('default_price.json', $response->getContent());
?>
