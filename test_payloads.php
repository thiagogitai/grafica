<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Http\Request;
use App\Http\Controllers\ProductPriceController;

function quote(array $payload) {
    $controller = app(ProductPriceController::class);
    $request = Request::create('/api/product/validate-price','POST',$payload);
    $response = $controller->validatePrice($request);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE) . " => " . $response->getContent() . "\n";
}

$payload = [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 50,
    'formato_miolo_paginas' => '118x175mm',
    'papel_capa' => 'Cartão Triplex 250gr ',
    'cores_capa' => '4 cores Frente',
    'orelha_capa' => 'SEM ORELHA',
    'acabamento_capa' => 'Laminação FOSCA FRENTE (Acima de 240g)',
    'papel_miolo' => 'Offset 75gr',
    'cores_miolo' => '4 cores frente e verso',
    'miolo_sangrado' => 'NÃO',
    'quantidade_paginas_miolo' => 'Miolo 8 páginas',
    'acabamento_miolo' => 'Dobrado',
    'acabamento_livro' => 'Colado PUR',
    'guardas_livro' => 'SEM GUARDAS',
    'extras' => 'Nenhum',
    'frete' => 'Incluso',
    'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
    'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
];
quote($payload);
$payload['quantidade_paginas_miolo'] = 'Miolo 32 páginas';
quote($payload);
?>
