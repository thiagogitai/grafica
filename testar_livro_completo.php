<?php
/**
 * Teste completo do template impressao-de-livro
 * Testa múltiplas combinações e valida o mapeamento
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "🧪 TESTE COMPLETO - Template impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

// Carregar dados
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Teste 1: Validar mapeamento
echo "📋 TESTE 1: Validação de Mapeamento\n";
echo str_repeat("-", 70) . "\n";

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
    echo "✅ Todas as opções estão mapeadas!\n";
} else {
    echo "❌ " . count($opcoes_sem_mapeamento) . " opções sem mapeamento\n";
}

echo "\n";

// Teste 2: Testar chamada direta à API externa
echo "🔍 TESTE 2: Chamada à API Externa\n";
echo str_repeat("-", 70) . "\n";

$combinacoes_teste = [
    [
        'nome' => 'Combinação Básica',
        'opcoes' => [
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
        ]
    ],
    [
        'nome' => 'Combinação com Capa Dura',
        'opcoes' => [
            'formato_miolo_paginas' => '148x210mm (A5) ',
            'papel_capa' => 'Couche Brilho 170gr ',
            'cores_capa' => '4 cores Frente',
            'orelha_capa' => 'COM Orelha de 10cm',
            'acabamento_capa' => 'Verniz UV TOTAL FRENTE (Acima de 240g)',
            'papel_miolo' => 'Offset 90gr',
            'cores_miolo' => '1 cor frente e verso PRETO',
            'miolo_sangrado' => 'SIM',
            'quantidade_paginas_miolo' => 'Miolo 52 páginas',
            'acabamento_miolo' => 'Dobrado',
            'acabamento_livro' => 'Capa Dura Papelão 15 (2,2mm) + Cola PUR',
            'guardas_livro' => 'SEM GUARDAS',
            'extras' => 'Shrink Coletivo c/ 10 peças',
            'frete' => 'Cliente Retira',
            'verificacao_arquivo' => 'Digital On-Line - via Web-Approval ou PDF',
            'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
        ]
    ],
    [
        'nome' => 'Combinação Mínima',
        'opcoes' => [
            'formato_miolo_paginas' => '118x175mm',
            'papel_capa' => 'SEM CAPA',
            'cores_capa' => '1 Cor Frente (Preto)',
            'orelha_capa' => 'SEM ORELHA',
            'acabamento_capa' => 'Laminação FOSCA FRENTE (Acima de 240g)',
            'papel_miolo' => 'Offset 75gr',
            'cores_miolo' => '1 cor frente e verso PRETO',
            'miolo_sangrado' => 'NÃO',
            'quantidade_paginas_miolo' => 'Miolo 8 páginas',
            'acabamento_miolo' => 'Dobrado',
            'acabamento_livro' => 'Grampeado - 2 grampos',
            'guardas_livro' => 'SEM GUARDAS',
            'extras' => 'Nenhum',
            'frete' => 'Incluso',
            'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)',
            'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*',
        ]
    ],
];

$sucesso = 0;
$erros = 0;

foreach ($combinacoes_teste as $idx => $combinacao) {
    echo "\n📦 Testando: {$combinacao['nome']}\n";
    
    try {
        // Criar requisição
        $request = new \Illuminate\Http\Request();
        $request->merge($combinacao['opcoes']);
        $request->merge(['product_slug' => 'impressao-de-livro', 'quantity' => 50]);
        
        // Chamar controller
        $controller = $app->make(\App\Http\Controllers\ApiPricingProxyController::class);
        $response = $controller->obterPreco($request);
        $data = json_decode($response->getContent(), true);
        
        if ($response->getStatusCode() === 200 && isset($data['price'])) {
            echo "   ✅ Sucesso!\n";
            echo "   💰 Preço: R$ " . number_format($data['price'], 2, ',', '.') . "\n";
            $sucesso++;
        } else {
            echo "   ❌ Erro!\n";
            if (isset($data['error'])) {
                echo "   ⚠️  {$data['error']}\n";
            } else {
                echo "   ⚠️  Resposta: " . substr($response->getContent(), 0, 100) . "\n";
            }
            $erros++;
        }
    } catch (Exception $e) {
        echo "   ❌ Exceção: " . $e->getMessage() . "\n";
        $erros++;
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTADO FINAL:\n";
echo "   ✅ Sucessos: {$sucesso}\n";
echo "   ❌ Erros: {$erros}\n";
echo "   📈 Taxa de sucesso: " . round(($sucesso / ($sucesso + $erros)) * 100, 1) . "%\n";

if ($sucesso === count($combinacoes_teste)) {
    echo "\n🎉 Todos os testes passaram!\n";
} else {
    echo "\n⚠️  Alguns testes falharam. Verifique os erros acima.\n";
}

