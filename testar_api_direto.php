<?php

/**
 * Testa diretamente o ApiPricingProxyController
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Simular requisição do frontend
$opcoes = [
    'formato_miolo_paginas' => '210x297mm (A4) ',
    'papel_capa' => 'Couche Fosco 210gr ',
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
    'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
];

echo "🧪 Testando API diretamente...\n\n";
echo "Opções enviadas:\n";
foreach ($opcoes as $k => $v) {
    echo "  {$k}: '{$v}' (length: " . strlen($v) . ")\n";
}
echo "\n";

try {
    // Criar Request como o ProductPriceController faz
    $requestData = [
        'product_slug' => 'impressao-de-livro',
        'quantity' => 51,
    ] + $opcoes;
    
    $request = new \Illuminate\Http\Request($requestData);
    
    $controller = $app->make(\App\Http\Controllers\ApiPricingProxyController::class);
    
    $resultado = $controller->obterPreco($request);
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "📤 RESPOSTA DA API\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    if ($resultado instanceof \Illuminate\Http\JsonResponse) {
        $data = json_decode($resultado->getContent(), true);
        $status = $resultado->getStatusCode();
        
        echo "Status: {$status}\n";
        print_r($data);
        
        if ($status === 200 && isset($data['success']) && $data['success']) {
            echo "\n✅ SUCESSO! Preço: R$ " . number_format($data['price'] ?? 0, 2, ',', '.') . "\n";
        } else {
            echo "\n❌ ERRO na requisição\n";
            if (isset($data['error'])) {
                echo "Erro: {$data['error']}\n";
            }
        }
    } else {
        echo "Resposta:\n";
        print_r($resultado);
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

