<?php
/**
 * Testar API de pricing diretamente no localhost (sem HTTP)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProductPriceController;

echo "🧪 TESTANDO API DE PRICING DIRETAMENTE (SEM HTTP)\n";
echo str_repeat("=", 80) . "\n\n";

// Opções de teste
$opcoes_teste = [
    'quantidade' => 50,
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

echo "📋 Opções de teste:\n";
foreach ($opcoes_teste as $campo => $valor) {
    echo "   {$campo}: {$valor}\n";
}

echo "\n📡 Chamando ProductPriceController->validatePrice() diretamente...\n\n";

try {
    $request = new Request([
        'product_slug' => 'impressao-de-livro',
        'quantity' => $opcoes_teste['quantidade'],
    ] + $opcoes_teste);
    
    $controller = new ProductPriceController();
    $response = $controller->validatePrice($request);
    
    $statusCode = $response->getStatusCode();
    $data = json_decode($response->getContent(), true);
    
    echo "Status HTTP: {$statusCode}\n\n";
    
    if ($statusCode === 200) {
        echo "✅ RESPOSTA:\n";
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        if (isset($data['success']) && $data['success']) {
            echo "\n✅ SUCESSO! Preço obtido: R$ " . number_format($data['price'], 2, ',', '.') . "\n";
            echo "   Validado: " . ($data['validated'] ? 'Sim' : 'Não') . "\n";
        } else {
            echo "\n❌ Erro na resposta: " . ($data['error'] ?? 'Erro desconhecido') . "\n";
        }
    } else {
        echo "❌ Erro HTTP: {$statusCode}\n";
        echo "Resposta: " . $response->getContent() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exceção: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

