<?php

/**
 * Testa 10 combinações rapidamente para validar o sistema
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

// Função para obter preço via proxy
function obterPrecoViaProxy($valores, $quantidade = 50) {
    $session = curl_init();
    $cookieFile = sys_get_temp_dir() . '/cookies_test_' . uniqid() . '.txt';
    
    // Obter CSRF token
    curl_setopt($session, CURLOPT_URL, "http://localhost:8000");
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($session, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
    $html = curl_exec($session);
    
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
    $csrfToken = $matches[1] ?? '';
    
    // Fazer requisição ao proxy
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
    
    @unlink($cookieFile);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}", 'response' => $response];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Resposta inválida', 'response' => $response];
    }
    
    if (isset($data['success']) && $data['success'] && isset($data['price'])) {
        return ['success' => true, 'price' => (float) $data['price']];
    }
    
    return ['success' => false, 'error' => $data['error'] ?? 'Erro desconhecido', 'response' => $response];
}

// Gerar 10 combinações
echo "🔍 Gerando 10 combinações de teste...\n\n";

$combinacoes = [];
$maxTentativas = 500;
$tentativas = 0;

while (count($combinacoes) < 10 && $tentativas < $maxTentativas) {
    $tentativas++;
    
    // Gerar combinação aleatória
    $valores = [];
    foreach ($ordemSelects as $campo) {
        if (isset($opcoesPorCampo[$campo]) && !empty($opcoesPorCampo[$campo])) {
            $valores[$campo] = $opcoesPorCampo[$campo][array_rand($opcoesPorCampo[$campo])];
        }
    }
    
    // Verificar se tem todos os campos obrigatórios
    if (count($valores) >= 15) {
        $combinacoes[] = ['valores' => $valores];
    }
}

echo "✅ Geradas " . count($combinacoes) . " combinações válidas\n\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "TESTANDO 10 COMBINAÇÕES\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$resultados = [];
$sucessos = 0;
$erros = 0;

foreach (array_values($combinacoes) as $idx => $combo) {
    $num = $idx + 1;
    echo "[{$num}/10] Testando combinação...\r";
    
    // Aguardar um pouco para não sobrecarregar
    if ($num > 1) {
        sleep(2);
    }
    
    $resultado = obterPrecoViaProxy($combo['valores'], 50);
    
    if ($resultado['success']) {
        $sucessos++;
        $resultados[] = [
            'num' => $num,
            'price' => $resultado['price'],
            'status' => 'OK'
        ];
        echo "[{$num}/10] ✅ OK - R$ " . number_format($resultado['price'], 2, ',', '.') . "\n";
    } else {
        $erros++;
        $resultados[] = [
            'num' => $num,
            'error' => $resultado['error'],
            'status' => 'ERRO'
        ];
        echo "[{$num}/10] ❌ ERRO: " . $resultado['error'] . "\n";
        
        // Mostrar primeira combinação que deu erro
        if ($erros === 1) {
            echo "   Valores: " . json_encode($combo['valores'], JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
}

echo "\n\n" . str_repeat("=", 70) . "\n";
echo "RESULTADO FINAL\n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ Sucessos: {$sucessos}/10\n";
echo "❌ Erros: {$erros}/10\n\n";

if ($sucessos === 10) {
    echo "✅ PERFEITO! Todos os 10 testes passaram!\n";
} elseif ($sucessos >= 8) {
    echo "✅ BOM! {$sucessos}/10 testes passaram\n";
} else {
    echo "❌ ATENÇÃO! Apenas {$sucessos}/10 testes passaram\n";
}



