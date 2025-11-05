<?php
/**
 * Testar diferentes combinações de opções para encontrar uma válida
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

// Carregar template
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);

// Função para mapear opções para Keys
function mapearParaKeys($opcoes, $keys_livro) {
    $options = [];
    foreach ($opcoes as $campo => $valor) {
        if ($campo === 'quantidade') {
            continue;
        }
        
        $valor_str = trim((string) $valor);
        
        // Match exato
        if (isset($keys_livro[$valor_str])) {
            $options[] = [
                'Key' => $keys_livro[$valor_str],
                'Value' => $valor_str
            ];
            continue;
        }
        
        // Match case-insensitive
        foreach ($keys_livro as $key_texto => $key_value) {
            if (strcasecmp(trim($key_texto), $valor_str) === 0) {
                $options[] = [
                    'Key' => $key_value,
                    'Value' => trim($key_texto)
                ];
                break;
            }
        }
    }
    return $options;
}

// Função para testar combinação
function testarCombinacao($opcoes, $keys_livro) {
    $options = mapearParaKeys($opcoes, $keys_livro);
    
    if (count($options) < count($opcoes) - 1) {
        return ['success' => false, 'error' => 'Keys não encontradas'];
    }
    
    $url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing";
    $payload = [
        'pricingParameters' => [
            'Q1' => (string) $opcoes['quantidade'],
            'Options' => $options
        ]
    ];
    
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
                return ['success' => false, 'error' => $data['ErrorMessage']];
            }
            
            if (isset($data['Cost'])) {
                $preco = (float) str_replace(',', '.', $data['Cost']);
                return ['success' => true, 'preco' => $preco, 'data' => $data];
            }
        }
        
        return ['success' => false, 'error' => 'HTTP ' . $response->status()];
    } catch (\Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "🧪 TESTANDO DIFERENTES COMBINAÇÕES DE OPÇÕES\n";
echo str_repeat("=", 80) . "\n\n";

// Pegar opções do template
$campos_opcoes = [];
foreach ($template['options'] as $opcao_config) {
    $campo = $opcao_config['name'] ?? '';
    $tipo = $opcao_config['type'] ?? '';
    
    if ($campo === 'quantity' || $tipo === 'number') {
        continue;
    }
    
    $choices = $opcao_config['choices'] ?? [];
    $opcoes_array = [];
    foreach ($choices as $choice) {
        $value = $choice['value'] ?? $choice['label'] ?? '';
        if ($value) {
            $opcoes_array[] = trim($value);
        }
    }
    
    if (!empty($opcoes_array)) {
        $campos_opcoes[$campo] = $opcoes_array;
    }
}

// Testar algumas combinações
$combinacoes = [
    // Combinação 1: opções mais simples/básicas
    [
        'quantidade' => 50,
        'formato_miolo_paginas' => $campos_opcoes['formato_miolo_paginas'][0] ?? '',
        'papel_capa' => $campos_opcoes['papel_capa'][0] ?? '',
        'cores_capa' => $campos_opcoes['cores_capa'][0] ?? '',
        'orelha_capa' => $campos_opcoes['orelha_capa'][0] ?? '',
        'acabamento_capa' => $campos_opcoes['acabamento_capa'][0] ?? '',
        'papel_miolo' => $campos_opcoes['papel_miolo'][0] ?? '',
        'cores_miolo' => $campos_opcoes['cores_miolo'][0] ?? '',
        'miolo_sangrado' => $campos_opcoes['miolo_sangrado'][0] ?? '',
        'quantidade_paginas_miolo' => $campos_opcoes['quantidade_paginas_miolo'][0] ?? '',
        'acabamento_miolo' => $campos_opcoes['acabamento_miolo'][0] ?? '',
        'acabamento_livro' => $campos_opcoes['acabamento_livro'][0] ?? '',
        'guardas_livro' => $campos_opcoes['guardas_livro'][0] ?? '',
        'extras' => $campos_opcoes['extras'][0] ?? '',
        'frete' => $campos_opcoes['frete'][0] ?? '',
        'verificacao_arquivo' => $campos_opcoes['verificacao_arquivo'][0] ?? '',
        'prazo_entrega' => $campos_opcoes['prazo_entrega'][0] ?? '',
    ],
    // Combinação 2: opções intermediárias
    [
        'quantidade' => 100,
        'formato_miolo_paginas' => $campos_opcoes['formato_miolo_paginas'][5] ?? $campos_opcoes['formato_miolo_paginas'][0] ?? '',
        'papel_capa' => $campos_opcoes['papel_capa'][1] ?? $campos_opcoes['papel_capa'][0] ?? '',
        'cores_capa' => $campos_opcoes['cores_capa'][1] ?? $campos_opcoes['cores_capa'][0] ?? '',
        'orelha_capa' => $campos_opcoes['orelha_capa'][0] ?? '',
        'acabamento_capa' => $campos_opcoes['acabamento_capa'][1] ?? $campos_opcoes['acabamento_capa'][0] ?? '',
        'papel_miolo' => $campos_opcoes['papel_miolo'][1] ?? $campos_opcoes['papel_miolo'][0] ?? '',
        'cores_miolo' => $campos_opcoes['cores_miolo'][0] ?? '',
        'miolo_sangrado' => $campos_opcoes['miolo_sangrado'][0] ?? '',
        'quantidade_paginas_miolo' => $campos_opcoes['quantidade_paginas_miolo'][1] ?? $campos_opcoes['quantidade_paginas_miolo'][0] ?? '',
        'acabamento_miolo' => $campos_opcoes['acabamento_miolo'][0] ?? '',
        'acabamento_livro' => $campos_opcoes['acabamento_livro'][1] ?? $campos_opcoes['acabamento_livro'][0] ?? '',
        'guardas_livro' => $campos_opcoes['guardas_livro'][0] ?? '',
        'extras' => $campos_opcoes['extras'][0] ?? '',
        'frete' => $campos_opcoes['frete'][0] ?? '',
        'verificacao_arquivo' => $campos_opcoes['verificacao_arquivo'][0] ?? '',
        'prazo_entrega' => $campos_opcoes['prazo_entrega'][0] ?? '',
    ],
];

$combinacao_num = 1;
foreach ($combinacoes as $opcoes) {
    echo "🧪 TESTE {$combinacao_num}:\n";
    echo "   Quantidade: {$opcoes['quantidade']}\n";
    echo "   Formato: {$opcoes['formato_miolo_paginas']}\n";
    echo "   Páginas: {$opcoes['quantidade_paginas_miolo']}\n";
    
    $resultado = testarCombinacao($opcoes, $keys_livro);
    
    if ($resultado['success']) {
        echo "\n   ✅ SUCESSO! Preço: R$ " . number_format($resultado['preco'], 2, ',', '.') . "\n";
        echo "   📋 Opções usadas:\n";
        foreach ($opcoes as $campo => $valor) {
            if ($campo !== 'quantidade') {
                echo "      - {$campo}: {$valor}\n";
            }
        }
        echo "\n";
        break;
    } else {
        echo "   ❌ Erro: {$resultado['error']}\n\n";
    }
    
    $combinacao_num++;
}

if (!isset($resultado['success']) || !$resultado['success']) {
    echo "\n❌ Nenhuma combinação testada funcionou.\n";
    echo "💡 Pode ser necessário testar manualmente no site matriz para encontrar combinações válidas.\n";
}

