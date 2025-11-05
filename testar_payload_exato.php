<?php
/**
 * Testar enviando o payload EXATO da requisição real que funcionou
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🧪 TESTANDO COM PAYLOAD EXATO DA REQUISIÇÃO REAL\n";
echo str_repeat("=", 80) . "\n\n";

// Payload EXATO da última requisição que funcionou (requisição 16)
$payload = [
    'pricingParameters' => [
        'KitParameters' => null,
        'Options' => [
            ['Key' => '8507966BFD1CED08D52954CA1BFBAFAC', 'Value' => '118x175mm'],
            ['Key' => '9DD0C964AA872B2B8F882356423C922D', 'Value' => 'Couche Brilho 150gr '],
            ['Key' => 'F54EB0969F0ACEBD67F0722A3FF633F3', 'Value' => '4 cores FxV'],
            ['Key' => 'FC83B57DD0039A0D73EC0FB9F63BDB59', 'Value' => 'COM Orelha de 8cm'],
            ['Key' => '9D50176D0602173B5575AC4A62173EA2', 'Value' => 'Laminação FOSCA Frente + UV Reserva (Acima de 240g)'],
            ['Key' => '2913797D83A57041C2A87BED6F1FEDA9', 'Value' => 'Pólen Natural 80g'],
            ['Key' => 'E90F9B0C705E3F28CE0D3B51613AE230', 'Value' => '1 cor frente e verso PRETO'],
            ['Key' => 'CFAB249F3402BE020FEFFD84CB991DAA', 'Value' => 'SIM'],
            ['Key' => 'FCDF130D17B1F0C1FB2503C6F33559D7', 'Value' => 'Miolo 12 páginas'],
            ['Key' => 'AFF7AA292FE40E02A7B255713E731899', 'Value' => 'Dobrado'],
            ['Key' => '3E9AFD1A94DA1802222717C0AAAC0093', 'Value' => 'Costurado'],
            ['Key' => '2211AA823438ACBE3BBCE2EF334AC4EA', 'Value' => 'offset 180g - sem impressão'],
            ['Key' => '07316319702E082CF6DA43BF4A1C130A', 'Value' => 'Shrink Individual'],
            ['Key' => '9F0D19D9628523760A8B7FF3464C9E9E', 'Value' => 'Cliente Retira'],
            ['Key' => 'A1EA4ABCE9F3330525CAD39BE77D01F7', 'Value' => 'Digital On-Line - via Web-Approval ou PDF'],
            ['Key' => '8C654A289F9D4F2A56C753120083C2ED', 'Value' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'],
        ],
        'Q1' => '25'
    ]
];

echo "📋 Payload (15 opções + Q1=25):\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing";

echo "📡 Enviando requisição exata...\n\n";

try {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro',
        'Origin' => 'https://www.lojagraficaeskenazi.com.br',
        'Connection' => 'keep-alive',
        'Sec-Fetch-Dest' => 'empty',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Site' => 'same-origin',
        'X-Requested-With' => 'XMLHttpRequest'
    ])->timeout(15)->post($url, $payload);
    
    echo "Status HTTP: " . $response->status() . "\n\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (!empty($data['ErrorMessage'])) {
            echo "❌ API retornou erro: {$data['ErrorMessage']}\n";
            echo "\nResposta completa:\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } elseif (isset($data['Cost'])) {
            $preco = (float) str_replace(',', '.', $data['Cost']);
            echo "✅ PREÇO OBTIDO: R$ " . number_format($preco, 2, ',', '.') . "\n";
            echo "   FormattedCost: " . ($data['FormattedCost'] ?? 'N/A') . "\n";
            echo "\n✅ SUCESSO! A API está funcionando!\n";
        } else {
            echo "❌ API não retornou campo Cost\n";
            echo "Resposta: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "❌ Erro HTTP: " . $response->status() . "\n";
        echo "Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Exceção: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

