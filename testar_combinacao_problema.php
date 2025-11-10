<?php

/**
 * Testa a combinação específica que estava dando erro
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Carregar mapeamento
$arquivo = 'mapeamento_keys_todos_produtos.json';
$mapeamento = json_decode(file_get_contents($arquivo), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Combinação que estava dando erro
$valoresTeste = [
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
        'formatted' => $data['FormattedCost'] ?? '',
        'data' => $data
    ];
}

echo "🧪 Testando combinação que estava dando erro...\n\n";

$options = [];
foreach ($ordemSelects as $idx => $campo) {
    if (!isset($valoresTeste[$campo])) {
        continue;
    }
    
    $valor = $valoresTeste[$campo];
    $valorTrimmed = trim($valor);
    $valorComEspaco = $valorTrimmed . ' ';
    
    $key = null;
    $valorFinal = null;
    
    // SEMPRE priorizar versão com espaço
    if (isset($keysMap[$valorComEspaco])) {
        $key = $keysMap[$valorComEspaco];
        $valorFinal = $valorComEspaco;
    } elseif (isset($keysMap[$valor])) {
        $key = $keysMap[$valor];
        $valorFinal = $valor;
    } elseif (isset($keysMap[$valorTrimmed])) {
        // Verificar se existe com espaço
        if (isset($keysMap[$valorComEspaco])) {
            $key = $keysMap[$valorComEspaco];
            $valorFinal = $valorComEspaco;
        } else {
            $key = $keysMap[$valorTrimmed];
            $valorFinal = $valorTrimmed;
        }
    }
    
    if ($key && $valorFinal) {
        $temEspaco = substr($valorFinal, -1) === ' ';
        echo "✅ {$campo}: '{$valorFinal}' (tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ") → {$key}\n";
        $options[] = ['Key' => $key, 'Value' => $valorFinal];
    } else {
        echo "❌ {$campo}: '{$valor}' → NÃO ENCONTRADO\n";
    }
}

echo "\nTotal de opções: " . count($options) . "\n\n";

if (count($options) < 15) {
    die("❌ Faltam opções!\n");
}

echo "═══════════════════════════════════════════════════════════\n";
echo "TESTANDO NA API\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$resultado = testarAPI($options, 50);

if ($resultado['success']) {
    echo "✅ SUCESSO!\n";
    echo "   Preço: R$ " . number_format($resultado['price'], 2, ',', '.') . "\n";
    echo "   Formatted: {$resultado['formatted']}\n";
    echo "\n✅ Sistema está funcionando perfeitamente!\n";
} else {
    echo "❌ ERRO: {$resultado['error']}\n";
    if (isset($resultado['data'])) {
        echo "   Dados: " . json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n";
    }
}

