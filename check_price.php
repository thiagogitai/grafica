<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Http\Request;
use App\Http\Controllers\ProductPriceController;

$request = Request::create('/api/product/validate-price', 'POST', [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 50
]);
$response = app(ProductPriceController::class)->validatePrice($request);
file_put_contents('check_price.json', $response->getContent());
?>
