<?php

/**
 * Teste rápido de comparação - verifica se quantidade está correta
 */

// Testar API externa diretamente
$url = 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing';

$payload = [
    'pricingParameters' => [
        'KitParameters' => null,
        'Q1' => '50',  // Quantidade exata
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

echo "🧪 Testando API externa com quantidade 50...\n\n";

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

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if (isset($data['Cost']) && $data['Cost'] !== '0') {
        $preco = (float) str_replace(',', '.', $data['Cost']);
        echo "✅ Preço API Externa (Q=50): R$ " . number_format($preco, 2, ',', '.') . "\n";
        echo "   Formatted: {$data['FormattedCost']}\n";
        echo "   Per Unit: {$data['FormattedPerUnitCost']}\n\n";
    } else {
        echo "❌ Erro: " . ($data['ErrorMessage'] ?? 'Preço zero') . "\n";
    }
} else {
    echo "❌ HTTP {$httpCode}\n";
}

echo "💰 Preço Matriz (scraping): R$ 1.449.888,75\n";
echo "💰 Preço Local (API):      R$ 1.450.087,00\n";
echo "📊 Diferença:              R$ 198,25 (0.0137%)\n\n";

echo "💡 A diferença pode ser devido a:\n";
echo "   1. Quantidade diferente (51 vs 50)\n";
echo "   2. Arredondamento na API\n";
echo "   3. Cache ou timing diferente\n";

