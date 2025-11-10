<?php

/**
 * Testa a requisição exatamente como o frontend faz
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simular requisição do frontend
$opcoes = [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 51,
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

echo "🧪 Testando requisição real do frontend...\n\n";
echo "Opções enviadas:\n";
print_r($opcoes);
echo "\n";

// Criar requisição
$request = Illuminate\Http\Request::create('/api/product/validate-price', 'POST', $opcoes);
$request->headers->set('Content-Type', 'application/json');
$request->headers->set('Accept', 'application/json');

// Obter CSRF token
$session = $app->make('session');
$token = $session->token();
$request->headers->set('X-CSRF-TOKEN', $token);

try {
    $response = $kernel->handle($request);
    $content = $response->getContent();
    $status = $response->getStatusCode();
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo "📤 RESPOSTA DA API\n";
    echo "═══════════════════════════════════════════════════════════\n";
    echo "Status: {$status}\n";
    echo "Resposta:\n";
    
    $data = json_decode($content, true);
    if ($data) {
        print_r($data);
    } else {
        echo $content;
    }
    
    if ($status === 200 && isset($data['success']) && $data['success']) {
        echo "\n✅ SUCESSO! Preço: R$ " . number_format($data['price'] ?? 0, 2, ',', '.') . "\n";
    } else {
        echo "\n❌ ERRO na requisição\n";
        if (isset($data['error'])) {
            echo "Erro: {$data['error']}\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEÇÃO: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

