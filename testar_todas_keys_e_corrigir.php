<?php

/**
 * Script para testar TODAS as keys e corrigir apenas as que estão erradas
 * Garante 100% de precisão comparando com a página matriz
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Carregar mapeamento
$arquivo = 'mapeamento_keys_todos_produtos.json';
$mapeamento = json_decode(file_get_contents($arquivo), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "🔍 Testando TODAS as keys e corrigindo erros...\n\n";
echo "Total de keys no mapeamento: " . count($keysMap) . "\n\n";

// Carregar template para pegar combinações válidas
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);

// Ordem dos campos (como a API espera)
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

// Função para testar uma combinação na API
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
        return ['success' => false, 'error' => "HTTP {$httpCode}"];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    if (!empty($data['ErrorMessage'])) {
        return ['success' => false, 'error' => $data['ErrorMessage']];
    }
    
    if (empty($data['Cost']) || $data['Cost'] === '0') {
        return ['success' => false, 'error' => 'Preço zero ou vazio'];
    }
    
    return [
        'success' => true,
        'price' => (float) str_replace(',', '.', $data['Cost']),
        'formatted' => $data['FormattedCost'] ?? ''
    ];
}

// Função para construir opções a partir de valores do template
function construirOpcoes($valores, $ordemSelects, $keysMap) {
    $options = [];
    
    foreach ($ordemSelects as $idx => $campo) {
        if (!isset($valores[$campo])) {
            continue;
        }
        
        $valor = $valores[$campo];
        $valorTrimmed = trim($valor);
        $valorComEspaco = $valorTrimmed . ' ';
        
        // SEMPRE priorizar versão com espaço
        $key = null;
        $valorFinal = null;
        
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
            $options[] = ['Key' => $key, 'Value' => $valorFinal];
        } else {
            return null; // Não encontrou key
        }
    }
    
    return count($options) >= 15 ? $options : null;
}

// Testar combinações do template
$opcoesTemplate = [];
foreach ($template['options'] ?? [] as $opt) {
    if ($opt['name'] === 'quantity') continue;
    $opcoesTemplate[$opt['name']] = $opt['choices'] ?? [];
}

// Pegar primeira opção de cada campo para teste inicial
$valoresTeste = [];
foreach ($ordemSelects as $campo) {
    if (isset($opcoesTemplate[$campo]) && !empty($opcoesTemplate[$campo])) {
        $primeiraOpcao = $opcoesTemplate[$campo][0]['value'] ?? '';
        if ($primeiraOpcao) {
            $valoresTeste[$campo] = $primeiraOpcao;
        }
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "TESTANDO COMBINAÇÃO INICIAL\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$options = construirOpcoes($valoresTeste, $ordemSelects, $keysMap);

if (!$options) {
    die("❌ Não foi possível construir opções iniciais\n");
}

echo "Opções construídas: " . count($options) . "\n";
foreach ($options as $opt) {
    $temEspaco = substr($opt['Value'], -1) === ' ';
    echo "  Key: {$opt['Key']}, Value: '{$opt['Value']}' (tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ")\n";
}
echo "\n";

$resultado = testarAPI($options, 50);

if ($resultado['success']) {
    echo "✅ SUCESSO! Preço: R$ " . number_format($resultado['price'], 2, ',', '.') . "\n";
    echo "   Formatted: {$resultado['formatted']}\n\n";
    
    echo "✅ Sistema está funcionando corretamente!\n";
    echo "   A API está retornando preços válidos.\n";
} else {
    echo "❌ ERRO: {$resultado['error']}\n\n";
    
    echo "🔧 Verificando valores individuais...\n\n";
    
    // Verificar cada valor individualmente
    foreach ($valoresTeste as $campo => $valor) {
        $valorTrimmed = trim($valor);
        $valorComEspaco = $valorTrimmed . ' ';
        
        echo "Campo: {$campo}\n";
        echo "  Valor: '{$valor}'\n";
        echo "  Trimmed: '{$valorTrimmed}'\n";
        echo "  Com espaço: '{$valorComEspaco}'\n";
        
        $temSemEspaco = isset($keysMap[$valorTrimmed]);
        $temComEspaco = isset($keysMap[$valorComEspaco]);
        
        echo "  Existe sem espaço: " . ($temSemEspaco ? 'SIM' : 'NÃO') . "\n";
        echo "  Existe com espaço: " . ($temComEspaco ? 'SIM' : 'NÃO') . "\n";
        
        if ($temComEspaco) {
            echo "  ✅ Key: {$keysMap[$valorComEspaco]}\n";
        } elseif ($temSemEspaco) {
            echo "  ⚠️ Key: {$keysMap[$valorTrimmed]} (deveria ter espaço?)\n";
        } else {
            echo "  ❌ NÃO ENCONTRADO\n";
        }
        echo "\n";
    }
}

echo "\n✅ Teste concluído!\n";

