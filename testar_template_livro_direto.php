<?php
/**
 * Testa o template de impressao-de-livro diretamente
 * Chama o controller sem passar pelo middleware
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "🧪 TESTE DIRETO - Template impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

// Carregar template e mapeamento
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "📊 Estatísticas:\n";
echo "   Total de campos: " . count($template['options']) . "\n";
$total_opcoes = 0;
foreach ($template['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        $total_opcoes += count($option['choices']);
    }
}
echo "   Total de opções: {$total_opcoes}\n";
echo "   Total de keys mapeadas: " . count($keys_livro) . "\n\n";

// Validar mapeamento
$opcoes_sem_mapeamento = [];
foreach ($template['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        foreach ($option['choices'] as $choice) {
            $valor = $choice['value'] ?? '';
            if ($valor) {
                $encontrada = false;
                foreach ($keys_livro as $key_texto => $key_id) {
                    if (strcasecmp(trim($key_texto), trim($valor)) === 0) {
                        $encontrada = true;
                        break;
                    }
                }
                if (!$encontrada) {
                    $opcoes_sem_mapeamento[] = [
                        'campo' => $option['name'],
                        'valor' => $valor
                    ];
                }
            }
        }
    }
}

if (empty($opcoes_sem_mapeamento)) {
    echo "✅ Todas as opções do template estão mapeadas!\n\n";
} else {
    echo "❌ Opções sem mapeamento (" . count($opcoes_sem_mapeamento) . "):\n";
    foreach (array_slice($opcoes_sem_mapeamento, 0, 10) as $item) {
        echo "   - {$item['campo']}: {$item['valor']}\n";
    }
    if (count($opcoes_sem_mapeamento) > 10) {
        echo "   ... e mais " . (count($opcoes_sem_mapeamento) - 10) . " opções\n";
    }
    echo "\n";
}

// Testar chamada direta ao ApiPricingProxyController
echo "🔍 Testando chamada à API externa...\n\n";

$opcoes_teste = [
    'formato_miolo_paginas' => '140x210mm',
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
    'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
];

try {
    $controller = $app->make(\App\Http\Controllers\ApiPricingProxyController::class);
    $request = new \Illuminate\Http\Request();
    $request->merge($opcoes_teste);
    $request->merge(['product_slug' => 'impressao-de-livro', 'quantity' => 50]);
    
    $response = $controller->obterPreco($request);
    $data = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 200 && isset($data['price'])) {
        echo "✅ Preço obtido com sucesso!\n";
        echo "   💰 Preço: R$ " . number_format($data['price'], 2, ',', '.') . "\n";
        if (isset($data['message'])) {
            echo "   📝 Mensagem: {$data['message']}\n";
        }
    } else {
        echo "❌ Erro ao obter preço:\n";
        if (isset($data['error'])) {
            echo "   ⚠️  Erro: {$data['error']}\n";
        } else {
            echo "   ⚠️  Resposta: " . $response->getContent() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Exceção:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Teste concluído!\n";

