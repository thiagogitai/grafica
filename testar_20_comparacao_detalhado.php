<?php

/**
 * Testa 20 combinações comparando MATRIZ vs LOCAL com intervalos maiores e debug detalhado
 */

// Carregar template e mapeamento
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

// Função para construir opções para API externa
function construirOpcoesAPI($valores, $ordemSelects, $keysMap) {
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
        
        // Priorizar versão com espaço
        if (isset($keysMap[$valorComEspaco])) {
            $key = $keysMap[$valorComEspaco];
            $valorFinal = $valorComEspaco;
        } elseif (isset($keysMap[$valor])) {
            $key = $keysMap[$valor];
            $valorFinal = $valor;
        } elseif (isset($keysMap[$valorTrimmed])) {
            $key = $keysMap[$valorTrimmed];
            $valorFinal = $valorTrimmed;
        }
        
        if ($key && $valorFinal) {
            $options[] = ['Key' => $key, 'Value' => $valorFinal];
        } else {
            return null; // Não encontrou key
        }
    }
    
    return count($options) >= 15 ? $options : null;
}

// Função para obter preço do SITE MATRIZ (API externa)
function obterPrecoMatriz($options, $quantidade = 50, $debug = false) {
    $url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing';
    
    $payload = [
        'pricingParameters' => [
            'KitParameters' => null,
            'Q1' => (string) $quantidade,
            'Options' => $options
        ]
    ];
    
    if ($debug) {
        echo "    [DEBUG MATRIZ] Chamando API externa...\n";
        echo "    [DEBUG MATRIZ] Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($debug) {
        echo "    [DEBUG MATRIZ] HTTP Code: {$httpCode}\n";
        echo "    [DEBUG MATRIZ] Response: " . substr($response, 0, 200) . "...\n";
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}", 'curl_error' => $curlError];
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        return ['success' => false, 'error' => 'Resposta inválida', 'raw_response' => substr($response, 0, 100)];
    }
    
    if (!empty($data['ErrorMessage'])) {
        return ['success' => false, 'error' => $data['ErrorMessage']];
    }
    
    if (empty($data['Cost']) || $data['Cost'] === '0') {
        return ['success' => false, 'error' => 'Preço zero ou vazio', 'data' => $data];
    }
    
    return [
        'success' => true,
        'price' => (float) str_replace(',', '.', $data['Cost']),
    ];
}

// Função para obter preço LOCAL
function obterPrecoLocal($opcoes, $quantidade = 50, $debug = false) {
    static $csrfToken = null;
    static $cookieFile = null;
    static $sessionCounter = 0;
    
    $sessionCounter++;
    
    // Criar novo cookie file a cada 5 requisições para evitar problemas
    if (!$cookieFile || $sessionCounter % 5 === 0) {
        if ($cookieFile && file_exists($cookieFile)) {
            @unlink($cookieFile);
        }
        $cookieFile = sys_get_temp_dir() . '/cookies_local_' . uniqid() . '.txt';
        $csrfToken = null; // Resetar token
    }
    
    $session = curl_init();
    
    // Obter CSRF token se necessário
    if (!$csrfToken) {
        if ($debug) {
            echo "    [DEBUG LOCAL] Obtendo CSRF token...\n";
        }
        curl_setopt($session, CURLOPT_URL, "http://localhost:8000");
        curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($session, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
        $html = curl_exec($session);
        
        preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
        $csrfToken = $matches[1] ?? '';
        
        if ($debug) {
            echo "    [DEBUG LOCAL] CSRF Token: " . substr($csrfToken, 0, 20) . "...\n";
        }
    }
    
    // Fazer requisição ao proxy local
    $url = "http://localhost:8000/api/pricing-proxy";
    $payload = array_merge([
        'product_slug' => 'impressao-de-livro',
        'quantity' => $quantidade,
    ], $opcoes);
    
    if ($debug) {
        echo "    [DEBUG LOCAL] Chamando proxy local...\n";
        echo "    [DEBUG LOCAL] Payload: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    curl_setopt($session, CURLOPT_URL, $url);
    curl_setopt($session, CURLOPT_POST, true);
    curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($session, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-CSRF-TOKEN: ' . $csrfToken,
        'X-Requested-With: XMLHttpRequest',
    ]);
    curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($session, CURLOPT_TIMEOUT, 60); // Timeout maior
    
    // Capturar apenas o corpo da resposta (ignorar headers)
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($session);
    $httpCode = curl_getinfo($session, CURLINFO_HTTP_CODE);
    $curlError = curl_error($session);
    curl_close($session);
    
    if ($debug) {
        echo "    [DEBUG LOCAL] HTTP Code: {$httpCode}\n";
        echo "    [DEBUG LOCAL] Response length: " . strlen($response) . "\n";
        echo "    [DEBUG LOCAL] Response (first 500 chars): " . substr($response, 0, 500) . "...\n";
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => "HTTP {$httpCode}", 'curl_error' => $curlError, 'response' => substr($response, 0, 200)];
    }
    
    // Encontrar o último JSON válido na resposta (pode ter output antes)
    // Procura por JSON objects no formato {"success":...}
    $jsonFound = null;
    if (preg_match_all('/\{[^{}]*"success"[^{}]*\}/', $response, $matches)) {
        // Pegar o último match (o JSON mais completo)
        foreach ($matches[0] as $match) {
            $testData = json_decode($match, true);
            if ($testData && isset($testData['success'])) {
                $jsonFound = $match;
            }
        }
    }
    
    // Se não encontrou JSON simples, tentar JSON completo (pode ter aninhamento)
    if (!$jsonFound && preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*"success"[^}]*\}/s', $response, $matches)) {
        $jsonFound = $matches[0];
    }
    
    // Última tentativa: procurar qualquer JSON válido
    if (!$jsonFound) {
        // Procurar o último { que começa um JSON válido
        $lastBrace = strrpos($response, '{');
        if ($lastBrace !== false) {
            $potentialJson = substr($response, $lastBrace);
            $testData = json_decode($potentialJson, true);
            if ($testData && json_last_error() === JSON_ERROR_NONE) {
                $jsonFound = $potentialJson;
            }
        }
    }
    
    if (!$jsonFound) {
        // Se não encontrou JSON, tentar usar a resposta inteira (pode funcionar se não houver output extra)
        $jsonFound = trim($response);
    }
    
    $data = json_decode($jsonFound, true);
    $jsonError = json_last_error();
    
    if ($debug) {
        echo "    [DEBUG LOCAL] JSON Found length: " . strlen($jsonFound) . "\n";
        echo "    [DEBUG LOCAL] JSON Error: {$jsonError}\n";
        if ($jsonError !== JSON_ERROR_NONE) {
            echo "    [DEBUG LOCAL] JSON Error Msg: " . json_last_error_msg() . "\n";
        } else {
            echo "    [DEBUG LOCAL] Data decoded: " . json_encode($data) . "\n";
        }
    }
    
    if (!$data || $jsonError !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida: ' . json_last_error_msg(), 'raw_response' => substr($response, 0, 500)];
    }
    
    if (isset($data['success']) && $data['success'] === true && isset($data['price'])) {
        return ['success' => true, 'price' => (float) $data['price']];
    }
    
    return ['success' => false, 'error' => $data['error'] ?? 'Erro desconhecido', 'data' => $data];
}

// Gerar 20 combinações
$numTestes = 20;
echo "🔍 Gerando {$numTestes} combinações de teste...\n\n";

$combinacoes = [];
$maxTentativas = 200;
$tentativas = 0;

while (count($combinacoes) < $numTestes && $tentativas < $maxTentativas) {
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
        $options = construirOpcoesAPI($valores, $ordemSelects, $keysMap);
        if ($options) {
            $combinacoes[] = [
                'valores' => $valores,
                'options' => $options
            ];
        }
    }
}

echo "✅ Geradas " . count($combinacoes) . " combinações válidas\n\n";
echo "=" . str_repeat("=", 70) . "\n";
echo "TESTANDO COMPARAÇÃO: SITE MATRIZ vs LOCAL (20 testes com intervalos maiores)\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$resultados = [];
$sucessos = 0;
$erros = 0;
$diferencas = [];
$inicio = time();

foreach (array_values($combinacoes) as $idx => $combo) {
    $num = $idx + 1;
    
    echo "\n[TESTE {$num}/{$numTestes}] Iniciando teste...\n";
    
    // Intervalo maior: 5 segundos entre testes
    if ($num > 1) {
        echo "    Aguardando 5 segundos (rate limiting)...\n";
        sleep(5);
    }
    
    // Testar SITE MATRIZ
    echo "    📡 Testando API MATRIZ...\n";
    $debug = ($num <= 3); // Debug apenas nos 3 primeiros
    $resultadoMatriz = obterPrecoMatriz($combo['options'], 50, $debug);
    
    if ($resultadoMatriz['success']) {
        echo "    ✅ MATRIZ: R$ " . number_format($resultadoMatriz['price'], 2, ',', '.') . "\n";
    } else {
        echo "    ❌ MATRIZ ERRO: " . $resultadoMatriz['error'] . "\n";
    }
    
    // Aguardar antes da segunda requisição
    echo "    Aguardando 3 segundos antes de testar LOCAL...\n";
    sleep(3);
    
    // Testar LOCAL
    echo "    🏠 Testando API LOCAL...\n";
    $resultadoLocal = obterPrecoLocal($combo['valores'], 50, $debug);
    
    if ($resultadoLocal['success']) {
        echo "    ✅ LOCAL: R$ " . number_format($resultadoLocal['price'], 2, ',', '.') . "\n";
    } else {
        echo "    ❌ LOCAL ERRO: " . $resultadoLocal['error'];
        if (isset($resultadoLocal['curl_error']) && $resultadoLocal['curl_error']) {
            echo " (cURL: " . $resultadoLocal['curl_error'] . ")";
        }
        echo "\n";
    }
    
    // Comparar resultados
    if ($resultadoMatriz['success'] && $resultadoLocal['success']) {
        $precoMatriz = $resultadoMatriz['price'];
        $precoLocal = $resultadoLocal['price'];
        $diferenca = abs($precoMatriz - $precoLocal);
        $percentual = ($diferenca / $precoMatriz) * 100;
        
        $status = $diferenca < 0.01 ? '✅ IDÊNTICO' : ($percentual < 0.1 ? '⚠️ PRÓXIMO' : '❌ DIFERENTE');
        
        echo "    📊 COMPARAÇÃO: Diferença de R$ " . number_format($diferenca, 2, ',', '.') . " (" . number_format($percentual, 4, ',', '.') . "%) - {$status}\n";
        
        $resultados[] = [
            'num' => $num,
            'matriz' => $precoMatriz,
            'local' => $precoLocal,
            'diferenca' => $diferenca,
            'percentual' => $percentual,
            'status' => $status
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
            'matriz_error' => $resultadoMatriz['error'] ?? null,
            'local_error' => $resultadoLocal['error'] ?? null,
            'status' => 'ERRO'
        ];
        echo "    ❌ ERRO: Não foi possível comparar (Matriz ou Local falhou)\n";
    }
    
    echo "    ⏱️  Tempo decorrido: " . (time() - $inicio) . " segundos\n";
}

echo "\n\n" . str_repeat("=", 70) . "\n";
echo "RESULTADO FINAL - COMPARAÇÃO MATRIZ vs LOCAL\n";
echo str_repeat("=", 70) . "\n\n";

echo "✅ Idênticos (diferença < R$ 0,01): {$sucessos}/{$numTestes}\n";
echo "⚠️  Com diferença: " . count($diferencas) . "/{$numTestes}\n";
echo "❌ Erros: {$erros}/{$numTestes}\n";
echo "📊 Taxa de precisão: " . number_format(($sucessos / $numTestes) * 100, 2) . "%\n";
echo "⏱️  Tempo total: " . (time() - $inicio) . " segundos\n\n";

if (!empty($diferencas)) {
    echo "📊 Estatísticas das diferenças:\n";
    echo "   Média: R$ " . number_format(array_sum($diferencas) / count($diferencas), 2, ',', '.') . "\n";
    echo "   Máxima: R$ " . number_format(max($diferencas), 2, ',', '.') . "\n";
    echo "   Mínima: R$ " . number_format(min($diferencas), 2, ',', '.') . "\n\n";
}

if ($sucessos === $numTestes) {
    echo "✅ PERFEITO! Todos os {$numTestes} testes passaram - Sistema 100% preciso!\n";
} elseif ($sucessos >= ($numTestes * 0.95)) {
    echo "✅ EXCELENTE! {$sucessos}/{$numTestes} testes passaram - Sistema muito preciso!\n";
} elseif ($sucessos >= ($numTestes * 0.80)) {
    echo "⚠️ BOM! {$sucessos}/{$numTestes} testes passaram - Verificar diferenças pequenas\n";
} else {
    echo "❌ ATENÇÃO! Apenas {$sucessos}/{$numTestes} testes passaram - Verificar problemas\n";
    
    // Mostrar erros
    echo "\nErros encontrados:\n";
    foreach ($resultados as $r) {
        if (isset($r['matriz_error']) || isset($r['local_error'])) {
            echo "  [{$r['num']}] Matriz: " . ($r['matriz_error'] ?? 'OK') . " | Local: " . ($r['local_error'] ?? 'OK') . "\n";
        }
    }
}

// Mostrar resultados detalhados
echo "\n" . str_repeat("=", 70) . "\n";
echo "RESULTADOS DETALHADOS:\n";
echo str_repeat("=", 70) . "\n";
foreach ($resultados as $r) {
    if (isset($r['matriz']) && isset($r['local'])) {
        echo sprintf(
            "[%d] Matriz: R$ %s | Local: R$ %s | Dif: R$ %s (%.4f%%) | %s\n",
            $r['num'],
            number_format($r['matriz'], 2, ',', '.'),
            number_format($r['local'], 2, ',', '.'),
            number_format($r['diferenca'], 2, ',', '.'),
            $r['percentual'],
            $r['status']
        );
    } else {
        echo "[{$r['num']}] ERRO: Matriz=" . ($r['matriz_error'] ?? 'N/A') . " | Local=" . ($r['local_error'] ?? 'N/A') . "\n";
    }
}

