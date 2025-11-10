<?php
/**
 * Testa a API de livros diretamente com uma combinação conhecida
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

echo "🧪 TESTE DIRETO - API impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

// Carregar mapeamento
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

if (empty($keys_livro)) {
    die("❌ Mapeamento de impressao-de-livro não encontrado!\n");
}

echo "✅ Mapeamento carregado: " . count($keys_livro) . " keys\n\n";

// Combinação de teste (baseada em uma que sabemos que funciona)
$opcoes_teste = [
    'formato_miolo_paginas' => '140x210mm',
    'papel_capa' => 'Cartão Triplex 250gr ',
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

// Ordem exata para impressao-de-livro
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
    10 => 'acabamento_miolo',
    11 => 'acabamento_livro',
    12 => 'guardas_livro',
    13 => 'extras',
    14 => 'frete',
    15 => 'verificacao_arquivo',
    16 => 'prazo_entrega',
];

$options = [];

echo "📋 Mapeando opções na ordem correta:\n";
foreach ($ordemSelects as $selectIdx => $campo) {
    if (!isset($opcoes_teste[$campo])) {
        continue;
    }
    
    $valorStr = (string) $opcoes_teste[$campo];
    $valorStrTrimmed = trim($valorStr);
    
    // Procurar no mapeamento
    $encontrado = false;
    $key_encontrada = null;
    $valor_mapeado = null;
    
    // 1. Match exato
    if (isset($keys_livro[$valorStr])) {
        $key_encontrada = $keys_livro[$valorStr];
        $valor_mapeado = $valorStr;
        $encontrado = true;
    }
    // 2. Match case-insensitive
    elseif (isset($keys_livro[$valorStrTrimmed])) {
        $key_encontrada = $keys_livro[$valorStrTrimmed];
        $valor_mapeado = $valorStrTrimmed;
        $encontrado = true;
    }
    // 3. Match case-insensitive com busca
    else {
        foreach ($keys_livro as $texto => $key) {
            if (strcasecmp(trim($texto), $valorStrTrimmed) === 0) {
                $key_encontrada = $key;
                $valor_mapeado = $texto; // Preservar valor original do mapeamento
                $encontrado = true;
                break;
            }
        }
    }
    
    if ($encontrado) {
        $options[] = [
            'Key' => $key_encontrada,
            'Value' => $valor_mapeado
        ];
        echo "   ✅ [{$selectIdx}] {$campo}: {$valor_mapeado} => {$key_encontrada}\n";
    } else {
        echo "   ❌ [{$selectIdx}] {$campo}: {$valorStr} => NÃO ENCONTRADO\n";
    }
}

echo "\n📊 Total de opções mapeadas: " . count($options) . "\n";

if (count($options) < 15) {
    die("❌ Faltam opções! Esperado: 15, Encontrado: " . count($options) . "\n");
}

// Montar payload
$payload = [
    'pricingParameters' => [
        'KitParameters' => null,
        'Q1' => '50',
        'Options' => $options
    ]
];

echo "\n🔍 Testando API diretamente...\n";
echo "   URL: https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing\n";
echo "   Q1: 50\n";
echo "   Options: " . count($options) . "\n\n";

try {
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Referer' => 'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro',
        'Origin' => 'https://www.lojagraficaeskenazi.com.br',
    ])->timeout(10)->post(
        'https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing',
        $payload
    );
    
    echo "📡 Status HTTP: " . $response->status() . "\n";
    
    if ($response->successful()) {
        $data = $response->json();
        
        if (!empty($data['ErrorMessage'])) {
            echo "   ❌ Erro da API: {$data['ErrorMessage']}\n";
        } elseif (isset($data['Cost'])) {
            $preco = (float) str_replace(',', '.', $data['Cost']);
            echo "   ✅ Preço obtido: R$ " . number_format($preco, 2, ',', '.') . "\n";
        } else {
            echo "   ⚠️  Resposta inesperada: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "   ❌ Erro HTTP: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exceção: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 70) . "\n";

