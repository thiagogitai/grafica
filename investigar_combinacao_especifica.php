<?php

/**
 * Investiga combinação específica que existe na matriz mas não funciona na API
 */

$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Combinação que existe na matriz mas não funciona na API
$valores = [
    'formato_miolo_paginas' => '158x230mm',
    'papel_capa' => 'Couche Brilho 210gr',
    'cores_capa' => '5 cores Frente x 1 cor Preto Verso',
    'orelha_capa' => 'COM Orelha de 9cm',
    'acabamento_capa' => 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',
    'papel_miolo' => 'Impressão Offset - >500unidades',
    'cores_miolo' => '4 cores frente e verso',
    'miolo_sangrado' => 'SIM',
    'quantidade_paginas_miolo' => 'Miolo 944 páginas',
    'acabamento_miolo' => 'Dobrado',
    'acabamento_livro' => 'Capa Dura Papelão 18 (1,8mm) + Cola PUR',
    'guardas_livro' => 'Vergê Madrepérola180g (Creme) - Com Impressão 4x4 Escala',
    'extras' => 'Shrink Coletivo c/ 50 peças',
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

echo "🔍 Investigando combinação específica...\n\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "VERIFICANDO MAPEAMENTO DE CADA VALOR\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$options = [];
$problemas = [];

foreach ($ordemSelects as $idx => $campo) {
    if (!isset($valores[$campo])) {
        continue;
    }
    
    $valor = $valores[$campo];
    $valorTrimmed = trim($valor);
    $valorComEspaco = $valorTrimmed . ' ';
    
    echo "[{$idx}] Campo: {$campo}\n";
    echo "    Valor: '{$valor}'\n";
    echo "    Trimmed: '{$valorTrimmed}'\n";
    echo "    Com espaço: '{$valorComEspaco}'\n";
    
    $key = null;
    $valorFinal = null;
    $encontrado = false;
    
    // Verificar todas as possibilidades
    if (isset($keysMap[$valorComEspaco])) {
        $key = $keysMap[$valorComEspaco];
        $valorFinal = $valorComEspaco;
        $encontrado = true;
        echo "    ✅ Encontrado COM espaço\n";
    } elseif (isset($keysMap[$valor])) {
        $key = $keysMap[$valor];
        $valorFinal = $valor;
        $encontrado = true;
        echo "    ✅ Encontrado valor original\n";
    } elseif (isset($keysMap[$valorTrimmed])) {
        // Verificar se existe com espaço
        if (isset($keysMap[$valorComEspaco])) {
            $key = $keysMap[$valorComEspaco];
            $valorFinal = $valorComEspaco;
            echo "    ✅ Encontrado trimmed, usando COM espaço\n";
        } else {
            $key = $keysMap[$valorTrimmed];
            $valorFinal = $valorTrimmed;
            echo "    ✅ Encontrado trimmed\n";
        }
        $encontrado = true;
    } else {
        // Buscar similar
        $melhorMatch = null;
        $melhorScore = 0;
        
        foreach ($keysMap as $texto => $k) {
            $textoTrimmed = trim($texto);
            $score = 0;
            
            if (strcasecmp($textoTrimmed, $valorTrimmed) === 0) {
                $score = 100;
                if ($texto === $valor) {
                    $score = 110;
                }
            } elseif (stripos($textoTrimmed, $valorTrimmed) !== false || stripos($valorTrimmed, $textoTrimmed) !== false) {
                $score = 50;
            }
            
            if ($score > $melhorScore) {
                $melhorMatch = ['key' => $k, 'value' => $texto, 'score' => $score];
                $melhorScore = $score;
            }
        }
        
        if ($melhorMatch && $melhorScore >= 50) {
            $key = $melhorMatch['key'];
            $valorFinal = $melhorMatch['value'];
            $encontrado = true;
            echo "    ⚠️ Match parcial encontrado (score: {$melhorScore}): '{$melhorMatch['value']}'\n";
        } else {
            echo "    ❌ NÃO ENCONTRADO NO MAPEAMENTO!\n";
            $problemas[] = [
                'campo' => $campo,
                'valor' => $valor,
                'posicao' => $idx
            ];
        }
    }
    
    if ($encontrado && $key) {
        $temEspaco = substr($valorFinal, -1) === ' ';
        echo "    Key: {$key}\n";
        echo "    Value final: '{$valorFinal}' (tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ")\n";
        $options[] = ['Key' => $key, 'Value' => $valorFinal];
    }
    
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════\n";
echo "RESUMO\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Total de opções construídas: " . count($options) . "\n";
echo "Problemas encontrados: " . count($problemas) . "\n\n";

if (!empty($problemas)) {
    echo "❌ CAMPOS COM PROBLEMA:\n";
    foreach ($problemas as $p) {
        echo "   [{$p['posicao']}] {$p['campo']}: '{$p['valor']}'\n";
    }
    echo "\n";
}

if (count($options) >= 15) {
    echo "✅ Opções suficientes, testando na API...\n\n";
    
    $url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing';
    $payload = [
        'pricingParameters' => [
            'KitParameters' => null,
            'Q1' => '50',
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['Cost']) && $data['Cost'] !== '0') {
        $preco = (float) str_replace(',', '.', $data['Cost']);
        echo "✅ SUCESSO! Preço: R$ " . number_format($preco, 2, ',', '.') . "\n";
    } else {
        echo "❌ ERRO na API\n";
        echo "   Status: {$httpCode}\n";
        if (isset($data['ErrorMessage'])) {
            echo "   Erro: {$data['ErrorMessage']}\n";
        }
        echo "\n   Payload enviado:\n";
        echo "   " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "❌ Não foi possível construir opções suficientes\n";
}

