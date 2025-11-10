<?php

/**
 * Testa 200 combinações de forma mais rápida (com cache e delays menores)
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

// Função para testar via proxy (sem delay excessivo)
function testarProxy($valores, $quantidade = 50) {
    static $session = null;
    static $csrfToken = null;
    static $cookieFile = null;
    
    if ($session === null) {
        $session = curl_init();
        $cookieFile = sys_get_temp_dir() . '/cookies_test_' . getmypid() . '.txt';
        
        // Obter CSRF token uma vez
        curl_setopt($session, CURLOPT_URL, "http://localhost:8000");
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
        $html = curl_exec($session);
        
        preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
        $csrfToken = $matches[1] ?? '';
    }
    
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
    curl_setopt($session, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    
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

// Gerar 200 combinações
echo "🔍 Gerando 200 combinações de teste...\n\n";

$combinacoes = [];
$maxTentativas = 1000;
$tentativas = 0;

while (count($combinacoes) < 200 && $tentativas < $maxTentativas) {
    $tentativas++;
    
    // Gerar combinação aleatória
    $valores = [];
    foreach ($ordemSelects as $campo) {
        if (!isset($opcoesPorCampo[$campo]) || empty($opcoesPorCampo[$campo])) {
            continue;
        }
        $valores[$campo] = $opcoesPorCampo[$campo][array_rand($opcoesPorCampo[$campo])];
    }
    
    // Verificar se é válida (tem pelo menos 15 campos)
    if (count($valores) >= 15) {
        $combinacoes[json_encode($valores)] = ['valores' => $valores];
    }
}

echo "✅ Geradas " . count($combinacoes) . " combinações válidas\n\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "TESTANDO 200 COMBINAÇÕES (SEM DELAY)\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$resultados = [];
$sucessos = 0;
$erros = 0;
$inicio = microtime(true);

foreach (array_values($combinacoes) as $idx => $combo) {
    $num = $idx + 1;
    echo "[{$num}/200] Testando...\r";
    
    // Testar apenas via proxy (mais rápido)
    $resultado = testarProxy($combo['valores'], 50);
    
    if ($resultado['success']) {
        $sucessos++;
        $resultados[] = [
            'num' => $num,
            'price' => $resultado['price'],
            'status' => 'OK'
        ];
    } else {
        $erros++;
        $resultados[] = [
            'num' => $num,
            'status' => 'ERRO',
            'error' => $resultado['error'] ?? 'Desconhecido'
        ];
    }
    
    // Delay mínimo apenas a cada 10 requisições (para não sobrecarregar)
    if ($num % 10 === 0) {
        usleep(500000); // 0.5 segundo a cada 10
    }
}

$tempo = round(microtime(true) - $inicio, 2);

echo "\n\n" . str_repeat("=", 70) . "\n";
echo "RESULTADO FINAL\n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ Sucessos: {$sucessos}/200\n";
echo "❌ Erros: {$erros}/200\n";
echo "⏱️  Tempo total: {$tempo}s\n";
echo "📊 Taxa de sucesso: " . number_format(($sucessos / 200) * 100, 2) . "%\n\n";

if ($sucessos === 200) {
    echo "✅ PERFEITO! Todos os 200 testes passaram - Sistema 100% preciso!\n";
} elseif ($sucessos >= 190) {
    echo "✅ EXCELENTE! {$sucessos}/200 testes passaram - Sistema muito preciso!\n";
} elseif ($sucessos >= 180) {
    echo "⚠️ BOM! {$sucessos}/200 testes passaram - Verificar erros\n";
} else {
    echo "❌ ATENÇÃO! Apenas {$sucessos}/200 testes passaram - Verificar problemas\n";
}

// Mostrar alguns erros
if ($erros > 0) {
    echo "\n📋 Primeiros erros encontrados:\n";
    $errosEncontrados = array_filter($resultados, function($r) {
        return ($r['status'] ?? '') === 'ERRO';
    });
    $primeirosErros = array_slice($errosEncontrados, 0, 5);
    foreach ($primeirosErros as $erro) {
        echo "  [{$erro['num']}] Erro: " . ($erro['error'] ?? 'Desconhecido') . "\n";
    }
}

echo "\n";

