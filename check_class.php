<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo class_exists(App\Http\Controllers\ProductPriceController::class) ? 'yes' : 'no';
?>
