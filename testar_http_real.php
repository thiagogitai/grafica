<?php

/**
 * Testa via HTTP real (simula frontend)
 */

$url = 'http://localhost:8000/api/product/validate-price';

$opcoes = [
    'product_slug' => 'impressao-de-livro',
    'quantity' => 51,
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

echo "🧪 Testando via HTTP real...\n\n";
echo "URL: {$url}\n";
echo "Opções enviadas:\n";
foreach ($opcoes as $k => $v) {
    $vDisplay = mb_strlen($v) > 50 ? mb_substr($v, 0, 50) . '...' : $v;
    echo "  {$k}: '{$vDisplay}' (length: " . mb_strlen($v) . ")\n";
}
echo "\n";

// Obter CSRF token primeiro
$ch = curl_init('http://localhost:8000');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');
$html = curl_exec($ch);
curl_close($ch);

// Extrair CSRF token
preg_match('/name="csrf-token" content="([^"]+)"/', $html, $matches);
$csrfToken = $matches[1] ?? '';

if (empty($csrfToken)) {
    // Tentar do meta tag
    preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $matches);
    $csrfToken = $matches[1] ?? '';
}

echo "CSRF Token: " . ($csrfToken ? '✅ Obtido' : '❌ Não encontrado') . "\n\n";

// Fazer requisição
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($opcoes));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-CSRF-TOKEN: ' . $csrfToken,
    'X-Requested-With: XMLHttpRequest',
]);
curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "═══════════════════════════════════════════════════════════\n";
echo "📤 RESPOSTA DA API\n";
echo "═══════════════════════════════════════════════════════════\n";
echo "Status HTTP: {$httpCode}\n";

if ($error) {
    echo "❌ Erro cURL: {$error}\n";
} else {
    $data = json_decode($response, true);
    if ($data) {
        echo "Resposta JSON:\n";
        print_r($data);
        
        if ($httpCode === 200 && isset($data['success']) && $data['success']) {
            echo "\n✅ SUCESSO! Preço: R$ " . number_format($data['price'] ?? 0, 2, ',', '.') . "\n";
        } else {
            echo "\n❌ ERRO na requisição\n";
            if (isset($data['error'])) {
                echo "Erro: {$data['error']}\n";
            }
        }
    } else {
        echo "Resposta (não JSON):\n";
        echo $response . "\n";
    }
}

