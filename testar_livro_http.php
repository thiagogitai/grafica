<?php
/**
 * Teste via HTTP real do template impressao-de-livro
 */

echo "🧪 TESTE HTTP - Template impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

// Verificar se servidor está rodando
$base_url = 'http://localhost:8000';

// Teste 1: Validar template
echo "📋 TESTE 1: Validação do Template\n";
echo str_repeat("-", 70) . "\n";

$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "   Total de campos: " . count($template['options']) . "\n";
$total_opcoes = 0;
foreach ($template['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        $total_opcoes += count($option['choices']);
    }
}
echo "   Total de opções: {$total_opcoes}\n";
echo "   Total de keys mapeadas: " . count($keys_livro) . "\n";

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
                    $opcoes_sem_mapeamento[] = $valor;
                }
            }
        }
    }
}

if (empty($opcoes_sem_mapeamento)) {
    echo "   ✅ Todas as opções estão mapeadas!\n";
} else {
    echo "   ❌ " . count($opcoes_sem_mapeamento) . " opções sem mapeamento\n";
}

echo "\n";

// Teste 2: Testar via HTTP
echo "🔍 TESTE 2: Chamada HTTP à API\n";
echo str_repeat("-", 70) . "\n";

$combinacao = [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 50,
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

echo "📤 Enviando requisição...\n";

$ch = curl_init($base_url . '/api/product/validate-price');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($combinacao));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    echo "   ❌ Erro cURL: {$curl_error}\n";
    echo "\n   💡 Dica: Certifique-se de que o servidor Laravel está rodando:\n";
    echo "      php artisan serve\n";
} else {
    echo "   📊 Status HTTP: {$http_code}\n";
    
    $data = json_decode($response, true);
    
    if ($http_code === 200 && isset($data['valid'])) {
        if ($data['valid']) {
            echo "   ✅ Preço válido!\n";
            if (isset($data['price'])) {
                echo "   💰 Preço: R$ " . number_format($data['price'], 2, ',', '.') . "\n";
            }
        } else {
            echo "   ❌ Preço inválido!\n";
            if (isset($data['error'])) {
                echo "   ⚠️  Erro: {$data['error']}\n";
            }
        }
    } elseif ($http_code === 419) {
        echo "   ⚠️  Erro CSRF (normal em testes diretos)\n";
        echo "   💡 Tente acessar via navegador ou desabilite CSRF para testes\n";
    } else {
        echo "   ⚠️  Resposta inesperada:\n";
        echo "   " . substr($response, 0, 200) . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "✅ Teste concluído!\n";

