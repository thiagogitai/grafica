<?php

/**
 * Testa diretamente na API externa com payload exato
 */

$url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing';

// Payload COM espaço (como deve ser) - quantidade 50 (mínimo)
$payloadComEspaco = [
    'pricingParameters' => [
        'KitParameters' => null,
        'Q1' => '50',
        'Options' => [
            ['Key' => '8507966BFD1CED08D52954CA1BFBAFAC', 'Value' => '210x297mm (A4) '],
            ['Key' => '9DD0C964AA872B2B8F882356423C922D', 'Value' => 'Couche Fosco 210gr '],
            ['Key' => 'F54EB0969F0ACEBD67F0722A3FF633F3', 'Value' => '4 cores Frente'],
            ['Key' => 'FC83B57DD0039A0D73EC0FB9F63BDB59', 'Value' => 'SEM ORELHA'],
            ['Key' => '9D50176D0602173B5575AC4A62173EA2', 'Value' => 'Laminação FOSCA FRENTE (Acima de 240g)'],
            ['Key' => '2913797D83A57041C2A87BED6F1FEDA9', 'Value' => 'Offset 75gr'],
            ['Key' => 'E90F9B0C705E3F28CE0D3B51613AE230', 'Value' => '4 cores frente e verso'],
            ['Key' => 'CFAB249F3402BE020FEFFD84CB991DAA', 'Value' => 'NÃO'],
            ['Key' => 'FCDF130D17B1F0C1FB2503C6F33559D7', 'Value' => 'Miolo 8 páginas'],
            ['Key' => 'AFF7AA292FE40E02A7B255713E731899', 'Value' => 'Dobrado'],
            ['Key' => '3E9AFD1A94DA1802222717C0AAAC0093', 'Value' => 'Colado PUR'],
            ['Key' => '2211AA823438ACBE3BBCE2EF334AC4EA', 'Value' => 'SEM GUARDAS'],
            ['Key' => '07316319702E082CF6DA43BF4A1C130A', 'Value' => 'Nenhum'],
            ['Key' => '9F0D19D9628523760A8B7FF3464C9E9E', 'Value' => 'Incluso'],
            ['Key' => 'A1EA4ABCE9F3330525CAD39BE77D01F7', 'Value' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)'],
            ['Key' => '8C654A289F9D4F2A56C753120083C2ED', 'Value' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'],
        ]
    ]
];

// Payload SEM espaço (como está sendo enviado)
$payloadSemEspaco = [
    'pricingParameters' => [
        'KitParameters' => null,
        'Q1' => '51',
        'Options' => [
            ['Key' => '8507966BFD1CED08D52954CA1BFBAFAC', 'Value' => '210x297mm (A4)'],
            ['Key' => '9DD0C964AA872B2B8F882356423C922D', 'Value' => 'Couche Fosco 210gr'],
            ['Key' => 'F54EB0969F0ACEBD67F0722A3FF633F3', 'Value' => '4 cores Frente'],
            ['Key' => 'FC83B57DD0039A0D73EC0FB9F63BDB59', 'Value' => 'SEM ORELHA'],
            ['Key' => '9D50176D0602173B5575AC4A62173EA2', 'Value' => 'Laminação FOSCA FRENTE (Acima de 240g)'],
            ['Key' => '2913797D83A57041C2A87BED6F1FEDA9', 'Value' => 'Offset 75gr'],
            ['Key' => 'E90F9B0C705E3F28CE0D3B51613AE230', 'Value' => '4 cores frente e verso'],
            ['Key' => 'CFAB249F3402BE020FEFFD84CB991DAA', 'Value' => 'NÃO'],
            ['Key' => 'FCDF130D17B1F0C1FB2503C6F33559D7', 'Value' => 'Miolo 8 páginas'],
            ['Key' => 'AFF7AA292FE40E02A7B255713E731899', 'Value' => 'Dobrado'],
            ['Key' => '3E9AFD1A94DA1802222717C0AAAC0093', 'Value' => 'Colado PUR'],
            ['Key' => '2211AA823438ACBE3BBCE2EF334AC4EA', 'Value' => 'SEM GUARDAS'],
            ['Key' => '07316319702E082CF6DA43BF4A1C130A', 'Value' => 'Nenhum'],
            ['Key' => '9F0D19D9628523760A8B7FF3464C9E9E', 'Value' => 'Incluso'],
            ['Key' => 'A1EA4ABCE9F3330525CAD39BE77D01F7', 'Value' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Grátis)'],
            ['Key' => '8C654A289F9D4F2A56C753120083C2ED', 'Value' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'],
        ]
    ]
];

echo "🧪 Testando API externa diretamente...\n\n";

// Teste 1: COM espaço
echo "═══════════════════════════════════════════════════════════\n";
echo "TESTE 1: Payload COM espaço\n";
echo "═══════════════════════════════════════════════════════════\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadComEspaco));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: {$httpCode1}\n";
$data1 = json_decode($response1, true);
if ($data1) {
    if (isset($data1['price'])) {
        echo "✅ SUCESSO! Preço: R$ " . number_format($data1['price'], 2, ',', '.') . "\n";
    } else {
        echo "❌ Erro: " . ($data1['error'] ?? json_encode($data1)) . "\n";
    }
} else {
    echo "Resposta: " . substr($response1, 0, 200) . "\n";
}

echo "\n";

// Teste 2: SEM espaço
echo "═══════════════════════════════════════════════════════════\n";
echo "TESTE 2: Payload SEM espaço\n";
echo "═══════════════════════════════════════════════════════════\n";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadSemEspaco));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: {$httpCode2}\n";
$data2 = json_decode($response2, true);
if ($data2) {
    if (isset($data2['price'])) {
        echo "✅ SUCESSO! Preço: R$ " . number_format($data2['price'], 2, ',', '.') . "\n";
    } else {
        echo "❌ Erro: " . ($data2['error'] ?? json_encode($data2)) . "\n";
    }
} else {
    echo "Resposta: " . substr($response2, 0, 200) . "\n";
}

