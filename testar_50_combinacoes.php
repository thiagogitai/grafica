<?php

/**
 * Testa 200 combinações diferentes e compara preços
 */

// Carregar template
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Ordem dos campos
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

// Extrair todas as opções de cada campo
$opcoesPorCampo = [];
foreach ($template['options'] ?? [] as $opt) {
    if ($opt['name'] === 'quantity') continue;
    $opcoesPorCampo[$opt['name']] = array_column($opt['choices'] ?? [], 'value');
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
        
        // SEMPRE priorizar versão com espaço
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
            return null; // Não encontrou key
        }
    }
    
    return count($options) >= 15 ? $options : null;
}

// Função para testar API externa via proxy local
function testarAPI($valores, $quantidade = 50) {
    $session = curl_init();
    $cookieFile = sys_get_temp_dir() . '/cookies_api.txt';
    
    // Obter CSRF token
    curl_setopt($session, CURLOPT_URL, "http://localhost:8000");
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($session);
    
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
    $csrfToken = $matches[1] ?? '';
    
    // Fazer requisição ao proxy local
    $url = "http://localhost:8000/api/pricing-proxy";
    $payload = array_merge([
        'product_slug' => 'impressao-de-livro',
        'quantity' => $quantidade,
    ], $valores);
    
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($session, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-CSRF-TOKEN: ' . $csrfToken,
        'X-Requested-With: XMLHttpRequest',
    ]);
    curl_setopt($session, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    curl_close($session);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}"];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    if (isset($data['success']) && $data['success'] && isset($data['price'])) {
        return ['success' => true, 'price' => (float) $data['price']];
    }
    
    return ['success' => false, 'error' => $data['error'] ?? 'Erro desconhecido'];
}

// Função para obter preço local (usando validate-price)
function obterPrecoLocal($opcoes, $quantidade = 50) {
    $session = curl_init();
    $cookieFile = sys_get_temp_dir() . '/cookies_local_' . uniqid() . '.txt';
    
    // Obter CSRF token
    curl_setopt($session, CURLOPT_URL, "http://localhost:8000");
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($session);
    
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
    $csrfToken = $matches[1] ?? '';
    
    // Fazer requisição
    $url = "http://localhost:8000/api/product/validate-price";
    $payload = array_merge([
        'product_slug' => 'impressao-de-livro',
        'quantity' => $quantidade,
    ], $opcoes);
    
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($session, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-CSRF-TOKEN: ' . $csrfToken,
        'X-Requested-With: XMLHttpRequest',
    ]);
    curl_setopt($session, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    curl_close($session);
    
    @unlink($cookieFile);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data['success'] ?? false && isset($data['price'])) {
            return ['success' => true, 'price' => (float) $data['price']];
        }
    }
    
    return ['success' => false, 'error' => "HTTP {$httpCode}"];
}

// Gerar 200 combinações aleatórias
echo "🔍 Gerando 200 combinações de teste...\n\n";

$combinacoes = [];
$maxTentativas = 200;
$tentativas = 0;

while (count($combinacoes) < 200 && $tentativas < $maxTentativas) {
    $tentativas++;
    
    // Gerar combinação aleatória
    $valores = [];
    foreach ($ordemSelects as $campo) {
        if (isset($opcoesPorCampo[$campo]) && !empty($opcoesPorCampo[$campo])) {
            $valores[$campo] = $opcoesPorCampo[$campo][array_rand($opcoesPorCampo[$campo])];
        }
    }
    
    // Verificar se consegue construir opções
    $options = construirOpcoes($valores, $ordemSelects, $keysMap);
    if ($options) {
        // Verificar se já existe (evitar duplicatas)
        $hash = md5(json_encode($valores));
        if (!isset($combinacoes[$hash])) {
            $combinacoes[$hash] = [
                'valores' => $valores,
                'options' => $options
            ];
        }
    }
}

echo "✅ Geradas " . count($combinacoes) . " combinações válidas\n\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "TESTANDO 200 COMBINAÇÕES\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$resultados = [];
$sucessos = 0;
$erros = 0;
$diferencas = [];

foreach (array_values($combinacoes) as $idx => $combo) {
    $num = $idx + 1;
    echo "[{$num}/200] Testando combinação...\r";
    
    // Rate limiting: aguardar entre requisições
    if ($num > 1) {
        sleep(2); // Aguardar 2 segundos entre requisições
    }
    
    // Testar API externa via proxy
    $resultadoAPI = testarAPI($combo['valores'], 50);
    
    // Aguardar mais um pouco antes da segunda requisição
    usleep(500000); // 0.5 segundos
    
    // Testar local
    $resultadoLocal = obterPrecoLocal($combo['valores'], 50);
    
    if ($resultadoAPI['success'] && $resultadoLocal['success']) {
        $precoAPI = $resultadoAPI['price'];
        $precoLocal = $resultadoLocal['price'];
        $diferenca = abs($precoAPI - $precoLocal);
        $percentual = ($diferenca / $precoAPI) * 100;
        
        $resultados[] = [
            'num' => $num,
            'api' => $precoAPI,
            'local' => $precoLocal,
            'diferenca' => $diferenca,
            'percentual' => $percentual,
            'status' => $diferenca < 0.01 ? 'IDÊNTICO' : ($percentual < 0.1 ? 'PRÓXIMO' : 'DIFERENTE')
        ];
        
        if ($diferenca < 0.01) {
            $sucessos++;
        } else {
            $diferencas[] = $diferenca;
        }
    } else {
        $erros++;
        $resultados[] = [
            'num' => $num,
            'api' => $resultadoAPI['success'] ? $resultadoAPI['price'] : null,
            'local' => $resultadoLocal['success'] ? $resultadoLocal['price'] : null,
            'erro' => $resultadoAPI['error'] ?? $resultadoLocal['error'] ?? 'Erro desconhecido',
            'status' => 'ERRO',
            'valores' => $combo['valores'], // Guardar valores para investigação
            'options' => $combo['options']  // Guardar options enviadas
        ];
    }
    
    // Rate limiting
    usleep(200000); // 0.2 segundos entre requisições
}

echo "\n\n" . str_repeat("=", 70) . "\n";
echo "RESULTADO FINAL\n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ Sucessos (idênticos): {$sucessos}/200\n";
echo "⚠️  Com diferença: " . count($diferencas) . "/200\n";
echo "❌ Erros: {$erros}/200\n\n";

if (!empty($diferencas)) {
    echo "📊 Estatísticas das diferenças:\n";
    echo "   Média: R$ " . number_format(array_sum($diferencas) / count($diferencas), 2, ',', '.') . "\n";
    echo "   Máxima: R$ " . number_format(max($diferencas), 2, ',', '.') . "\n";
    echo "   Mínima: R$ " . number_format(min($diferencas), 2, ',', '.') . "\n\n";
}

// Mostrar todos os erros detalhadamente
$errosDetalhados = array_filter($resultados, function($r) { return isset($r['erro']); });

if (!empty($errosDetalhados)) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "INVESTIGANDO ERROS - Combinações que não funcionaram\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    foreach ($errosDetalhados as $r) {
        echo "[{$r['num']}] ❌ ERRO: {$r['erro']}\n";
        echo "   Valores enviados:\n";
        foreach ($r['valores'] as $campo => $valor) {
            echo "     {$campo}: '{$valor}'\n";
        }
        echo "   Options enviadas para API:\n";
        foreach ($r['options'] as $opt) {
            $temEspaco = substr($opt['Value'], -1) === ' ';
            echo "     Key: {$opt['Key']}, Value: '{$opt['Value']}' (tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ")\n";
        }
        echo "\n";
    }
}

// Mostrar primeiros 10 resultados detalhados
echo "═══════════════════════════════════════════════════════════\n";
echo "Primeiros 10 resultados:\n";
echo str_repeat("-", 70) . "\n";
foreach (array_slice($resultados, 0, 10) as $r) {
    if (isset($r['erro'])) {
        echo "[{$r['num']}] ❌ ERRO: {$r['erro']}\n";
    } else {
        $status = $r['status'] === 'IDÊNTICO' ? '✅' : ($r['status'] === 'PRÓXIMO' ? '⚠️' : '❌');
        echo "[{$r['num']}] {$status} API: R$ " . number_format($r['api'], 2, ',', '.') . 
             " | Local: R$ " . number_format($r['local'], 2, ',', '.') . 
             " | Diff: R$ " . number_format($r['diferenca'], 2, ',', '.') . 
             " (" . number_format($r['percentual'], 4, ',', '.') . "%)\n";
    }
}

if (count($resultados) > 10) {
    echo "\n... e mais " . (count($resultados) - 10) . " resultados\n";
}

echo "\n";

if ($sucessos === 200) {
    echo "✅ PERFEITO! Todos os 200 testes passaram - Sistema 100% preciso!\n";
} elseif ($sucessos >= 190) {
    echo "✅ EXCELENTE! {$sucessos}/200 testes passaram - Sistema muito preciso!\n";
} elseif ($sucessos >= 180) {
    echo "⚠️ BOM! {$sucessos}/200 testes passaram - Verificar diferenças pequenas\n";
} else {
    echo "❌ ATENÇÃO! Apenas {$sucessos}/200 testes passaram - Verificar problemas\n";
}

