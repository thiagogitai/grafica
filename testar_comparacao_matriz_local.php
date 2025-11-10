<?php

/**
 * Testa comparando valores do SITE MATRIZ com valores LOCAIS
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
function obterPrecoMatriz($options, $quantidade = 50) {
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
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
    ];
}

// Função para obter preço LOCAL
function obterPrecoLocal($opcoes, $quantidade = 50) {
    static $csrfToken = null;
    static $cookieFile = null;
    
    if (!$cookieFile) {
        $cookieFile = sys_get_temp_dir() . '/cookies_local_' . uniqid() . '.txt';
    }
    
    $session = curl_init();
    
    // Obter CSRF token apenas uma vez
    if (!$csrfToken) {
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
    curl_setopt($session, CURLOPT_COOKIEFILE, $cookieFile);
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

// Gerar combinações
$numTestes = 200;
echo "🔍 Gerando {$numTestes} combinações de teste...\n\n";

$combinacoes = [];
$maxTentativas = 500;
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
echo "TESTANDO COMPARAÇÃO: SITE MATRIZ vs LOCAL\n";
echo "=" . str_repeat("=", 70) . "\n\n";

$resultados = [];
$sucessos = 0;
$erros = 0;
$diferencas = [];
$inicio = time();

foreach (array_values($combinacoes) as $idx => $combo) {
    $num = $idx + 1;
    
    // Rate limiting: aguardar entre requisições
    if ($num > 1) {
        sleep(2); // 2 segundos entre cada teste
    }
    
    // Testar SITE MATRIZ
    $resultadoMatriz = obterPrecoMatriz($combo['options'], 50);
    
    // Aguardar um pouco antes da segunda requisição
    usleep(500000); // 0.5 segundos
    
    // Testar LOCAL
    $resultadoLocal = obterPrecoLocal($combo['valores'], 50);
    
    if ($resultadoMatriz['success'] && $resultadoLocal['success']) {
        $precoMatriz = $resultadoMatriz['price'];
        $precoLocal = $resultadoLocal['price'];
        $diferenca = abs($precoMatriz - $precoLocal);
        $percentual = ($diferenca / $precoMatriz) * 100;
        
        $resultados[] = [
            'num' => $num,
            'matriz' => $precoMatriz,
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
            'matriz_error' => $resultadoMatriz['error'] ?? null,
            'local_error' => $resultadoLocal['error'] ?? null,
            'status' => 'ERRO'
        ];
    }
    
    // Mostrar progresso a cada 10 testes
    if ($num % 10 === 0 || $num === $numTestes) {
        $tempoDecorrido = time() - $inicio;
        $tempoEstimado = $num > 0 ? ($tempoDecorrido / $num) * ($numTestes - $num) : 0;
        $percentual = ($num / $numTestes) * 100;
        
        echo sprintf(
            "[%d/%d] Progresso: %.1f%% | Idênticos: %d | Com diferença: %d | Erros: %d | Tempo: %ds | ETA: %ds\n",
            $num,
            $numTestes,
            $percentual,
            $sucessos,
            count($diferencas),
            $erros,
            $tempoDecorrido,
            (int)$tempoEstimado
        );
    }
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
} elseif ($sucessos >= ($numTestes * 0.90)) {
    echo "⚠️ BOM! {$sucessos}/{$numTestes} testes passaram - Verificar diferenças pequenas\n";
} else {
    echo "❌ ATENÇÃO! Apenas {$sucessos}/{$numTestes} testes passaram - Verificar problemas\n";
    
    // Mostrar primeiros 5 erros
    echo "\nPrimeiros erros encontrados:\n";
    $errosMostrados = 0;
    foreach ($resultados as $r) {
        if (isset($r['matriz_error']) || isset($r['local_error'])) {
            if ($errosMostrados < 5) {
                echo "  [{$r['num']}] Matriz: " . ($r['matriz_error'] ?? 'OK') . " | Local: " . ($r['local_error'] ?? 'OK') . "\n";
                $errosMostrados++;
            }
        } elseif (isset($r['diferenca']) && $r['diferenca'] > 0.01) {
            if ($errosMostrados < 5) {
                echo "  [{$r['num']}] Diferença: R$ " . number_format($r['diferenca'], 2, ',', '.') . " ({$r['percentual']}%)\n";
                $errosMostrados++;
            }
        }
    }
}

// Mostrar primeiros 10 resultados detalhados
echo "\n" . str_repeat("=", 70) . "\n";
echo "Primeiros 10 resultados:\n";
echo str_repeat("=", 70) . "\n";
foreach (array_slice($resultados, 0, 10) as $r) {
    if (isset($r['matriz']) && isset($r['local'])) {
        echo sprintf(
            "[%d] Matriz: R$ %s | Local: R$ %s | Diferença: R$ %s (%.4f%%) | %s\n",
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



