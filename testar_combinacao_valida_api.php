<?php
/**
 * Testar a combinação válida descoberta via scraping
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

// Carregar Keys
$arquivo_mapeamento = __DIR__ . '/mapeamento_keys_todos_produtos.json';
$dados = json_decode(file_get_contents($arquivo_mapeamento), true);
$keys_livro = $dados['mapeamento_por_produto']['impressao-de-livro'] ?? [];

// Carregar combinação válida
$combinacao = json_decode(file_get_contents('combinacao_valida_livro.json'), true);

echo "🧪 TESTANDO COMBINAÇÃO VÁLIDA VIA API\n";
echo str_repeat("=", 80) . "\n\n";

// Mapear opções na ORDEM EXATA dos selects do site
// Baseado na última requisição capturada que funcionou
// IMPORTANTE: Alguns valores têm espaços extras no final!
$opcoes_na_ordem = [
    '118x175mm',  // select_0
    'Couche Brilho 150gr ',  // select_1 - TEM ESPAÇO NO FINAL!
    '4 cores FxV',  // select_2
    'COM Orelha de 8cm',  // select_3
    'Laminação FOSCA Frente + UV Reserva (Acima de 240g)',  // select_4
    'Pólen Natural 80g',  // select_5
    '1 cor frente e verso PRETO',  // select_6
    'SIM',  // select_7
    'Miolo 12 páginas',  // select_8
    'Costurado',  // select_10 (select_9 pulado)
    'offset 180g - sem impressão',  // select_11
    'Shrink Individual',  // select_12
    'Cliente Retira',  // select_13
    'Digital On-Line - via Web-Approval ou PDF',  // select_14
    'Padrão: 10 dias úteis de Produção + tempo de FRETE*',  // Última opção obrigatória
];

echo "📋 Opções na ordem dos selects do site:\n";
foreach ($opcoes_na_ordem as $idx => $valor) {
    if (!empty($valor)) {
        echo "   Select {$idx}: {$valor}\n";
    }
}

echo "\n📡 Mapeando para Keys (na ordem exata)...\n";

$options = [];
foreach ($opcoes_na_ordem as $idx => $valor) {
    if (empty($valor)) {
        continue;  // Pular select vazio
    }
    
    $valor_str = trim((string) $valor);
    
    // Match exato
    if (isset($keys_livro[$valor_str])) {
        $options[] = [
            'Key' => $keys_livro[$valor_str],
            'Value' => $valor_str
        ];
        echo "   ✅ Select {$idx}: '{$valor_str}'\n";
        continue;
    }
    
    // Match case-insensitive
    $encontrado = false;
    foreach ($keys_livro as $key_texto => $key_value) {
        if (strcasecmp(trim($key_texto), $valor_str) === 0) {
            $options[] = [
                'Key' => $key_value,
                'Value' => trim($key_texto)
            ];
            echo "   ✅ Select {$idx}: '{$valor_str}' → '{$key_texto}' (match CI)\n";
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        echo "   ❌ Key não encontrada para Select {$idx} = '{$valor_str}'\n";
    }
}

if (empty($options)) {
    echo "\n❌ Nenhuma opção foi mapeada para Keys!\n";
    exit(1);
}

echo "\n📊 Total de opções mapeadas: " . count($options) . "\n";

// Chamar API
$url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing";

$payload = [
    'pricingParameters' => [
        'KitParameters' => null,  // IMPORTANTE: Site envia isso!
        'Q1' => '25',  // Site usa 25 como padrão, não 50!
        'Options' => $options
    ]
];

echo "\n📡 Chamando API de pricing...\n";
echo "   URL: {$url}\n";
echo "   Q1: {$combinacao['quantidade']}\n";
echo "   Options: " . count($options) . " (na ordem dos selects)\n";
echo "\n📋 Payload completo:\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

try {
    $response = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/plain, */*',
        'Accept-Language' => 'pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro',
        'Origin' => 'https://www.lojagraficaeskenazi.com.br'
    ])->timeout(15)->post($url, $payload);
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (!empty($data['ErrorMessage'])) {
            echo "\n❌ API retornou erro: {$data['ErrorMessage']}\n";
            exit(1);
        }
        
        if (isset($data['Cost'])) {
            $preco = (float) str_replace(',', '.', $data['Cost']);
            echo "\n✅ PREÇO OBTIDO VIA API: R$ " . number_format($preco, 2, ',', '.') . "\n";
            echo "   FormattedCost: " . ($data['FormattedCost'] ?? 'N/A') . "\n";
            echo "\n💰 Preço do site (scraping): {$combinacao['preco']}\n";
            
            if ($preco > 0) {
                echo "\n✅ SUCESSO! A API está funcionando!\n";
            }
        } else {
            echo "\n❌ API não retornou campo Cost\n";
            echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "\n❌ Erro HTTP: " . $response->status() . "\n";
        echo "   Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Exceção: " . $e->getMessage() . "\n";
}

