<?php

/**
 * Investiga por que algumas combinações aparecem na matriz mas não funcionam na API
 */

// Carregar template e mapeamento
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Função para testar API
function testarAPI($options, $quantidade = 50) {
    $url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing';
    
    $payload = [
        'pricingParameters' => [
            'KitParameters' => null,
            'Q1' => (string) $quantidade,
            'Options' => $options
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}", 'response' => $response];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Resposta inválida', 'response' => $response];
    }
    
    if (!empty($data['ErrorMessage'])) {
        return ['success' => false, 'error' => $data['ErrorMessage'], 'data' => $data];
    }
    
    if (empty($data['Cost']) || $data['Cost'] === '0') {
        return ['success' => false, 'error' => 'Preço zero ou vazio', 'data' => $data];
    }
    
    return [
        'success' => true,
        'price' => (float) str_replace(',', '.', $data['Cost']),
        'formatted' => $data['FormattedCost'] ?? ''
    ];
}

// Função para construir opções
function construirOpcoes($valores, $ordemSelects, $keysMap) {
    $options = [];
    
    foreach ($ordemSelects as $idx => $campo) {
        if (!isset($valores[$campo])) {
            continue;
        }
        
        $valor = $valores[$campo];
        $valorTrimmed = trim($valor);
        $valorComEspaco = $valorTrimmed . ' ';
        
        $key = null;
        $valorFinal = null;
        
        if (isset($keysMap[$valorComEspaco])) {
            $key = $keysMap[$valorComEspaco];
            $valorFinal = $valorComEspaco;
        } elseif (isset($keysMap[$valor])) {
            $key = $keysMap[$valor];
            $valorFinal = $valor;
        } elseif (isset($keysMap[$valorTrimmed])) {
            if (isset($keysMap[$valorComEspaco])) {
                $key = $keysMap[$valorComEspaco];
                $valorFinal = $valorComEspaco;
            } else {
                $key = $keysMap[$valorTrimmed];
                $valorFinal = $valorTrimmed;
            }
        }
        
        if ($key && $valorFinal) {
            $options[] = ['Key' => $key, 'Value' => $valorFinal];
        } else {
            return ['error' => "Key não encontrada para {$campo} = {$valor}"];
        }
    }
    
    return count($options) >= 15 ? $options : ['error' => 'Menos de 15 opções'];
}

echo "🔍 Investigando combinações que podem dar erro...\n\n";

// Testar algumas combinações específicas que podem ser problemáticas
$testes = [
    [
        'nome' => 'Teste 1 - Combinação básica',
        'valores' => [
            'formato_miolo_paginas' => '210x297mm (A4)',
            'papel_capa' => 'Couche Fosco 210gr',
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
    ]
];

$ordemSelects = [
    0 => 'formato_miolo_paginas',
    1 => 'papel_capa',
    2 => 'cores_capa',
    3 => 'orelha_capa',
    4 => 'acabamento_capa',
    5 => 'papel_miolo',
    6 => 'cores_miolo',
    7 => 'miolo_sangrado',
    8 => 'quantidade_paginas_miolo',
    9 => 'acabamento_miolo',
    10 => 'acabamento_livro',
    11 => 'guardas_livro',
    12 => 'extras',
    13 => 'frete',
    14 => 'verificacao_arquivo',
    15 => 'prazo_entrega',
];

foreach ($testes as $teste) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "{$teste['nome']}\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $resultado = construirOpcoes($teste['valores'], $ordemSelects, $keysMap);
    
    if (isset($resultado['error'])) {
        echo "❌ Erro ao construir opções: {$resultado['error']}\n\n";
        continue;
    }
    
    echo "Opções construídas (" . count($resultado) . "):\n";
    foreach ($resultado as $opt) {
        $temEspaco = substr($opt['Value'], -1) === ' ';
        echo "  Key: {$opt['Key']}, Value: '{$opt['Value']}' (tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ")\n";
    }
    echo "\n";
    
    echo "Testando na API...\n";
    $apiResult = testarAPI($resultado, 50);
    
    if ($apiResult['success']) {
        echo "✅ SUCESSO! Preço: R$ " . number_format($apiResult['price'], 2, ',', '.') . "\n";
    } else {
        echo "❌ ERRO: {$apiResult['error']}\n";
        if (isset($apiResult['data'])) {
            echo "   Dados: " . json_encode($apiResult['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
}

