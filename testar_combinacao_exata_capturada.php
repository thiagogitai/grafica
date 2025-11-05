<?php
/**
 * Testar com a combinação EXATA que foi capturada e funcionou
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ProductPriceController;

echo "🧪 TESTANDO COM COMBINAÇÃO EXATA CAPTURADA (QUE FUNCIONOU)\n";
echo str_repeat("=", 80) . "\n\n";

// Última requisição capturada que funcionou (requisição 16)
// Q1: 25
// Options: 15 opções na ordem exata
$opcoes_exatas = [
    'quantidade' => 25,  // Q1 da requisição que funcionou
    'formato_miolo_paginas' => '118x175mm',
    'papel_capa' => 'Couche Brilho 150gr ',  // COM ESPAÇO NO FINAL!
    'cores_capa' => '4 cores FxV',
    'orelha_capa' => 'COM Orelha de 8cm',
    'acabamento_capa' => 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
    'papel_miolo' => 'Pólen Natural 80g',
    'cores_miolo' => '1 cor frente e verso PRETO',
    'miolo_sangrado' => 'SIM',
    'quantidade_paginas_miolo' => 'Miolo 12 páginas',
    // Na requisição real capturada (última que funcionou):
    // Posição 10: "Dobrado" (acabamento_miolo)
    // Posição 11: "Costurado" (acabamento_livro)
    // Posição 12: "offset 180g - sem impressão" (guardas_livro)
    // Posição 13: "Shrink Individual" (extras)
    'acabamento_miolo' => 'Dobrado',  // Posição 10
    'acabamento_livro' => 'Costurado',  // Posição 11
    'guardas_livro' => 'offset 180g - sem impressão',  // Posição 12
    'extras' => 'Shrink Individual',  // Posição 13
    'frete' => 'Cliente Retira',
    'verificacao_arquivo' => 'Digital On-Line - via Web-Approval ou PDF',
    'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
];

echo "📋 Opções (exatamente como na requisição que funcionou):\n";
foreach ($opcoes_exatas as $campo => $valor) {
    if ($valor === null) {
        echo "   {$campo}: (não enviado)\n";
        continue;
    }
    $espaco = strpos($valor, ' ') === strlen($valor) - 1 ? ' [ESPAÇO NO FINAL]' : '';
    echo "   {$campo}: '{$valor}'{$espaco}\n";
}

echo "\n📡 Chamando ProductPriceController->validatePrice()...\n\n";

try {
    // Filtrar valores null
    $opcoes_filtradas = array_filter($opcoes_exatas, function($v) {
        return $v !== null;
    });
    
    // IMPORTANTE: Usar quantity (não quantidade) e garantir que seja 25 como na requisição real
    $request = new Request([
        'product_slug' => 'impressao-de-livro',
        'quantity' => 25,  // Forçar 25 como na requisição real que funcionou
        'quantidade' => 25,  // Também enviar como quantidade
    ] + $opcoes_filtradas);
    
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

