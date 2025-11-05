<?php
/**
 * Script para testar preços de impressao-de-livro no VPS
 * Compara preços obtidos via API com valores esperados
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

// Carregar Keys mapeadas
$arquivo_mapeamento = base_path('mapeamento_keys_todos_produtos.json');
if (!file_exists($arquivo_mapeamento)) {
    echo "❌ Arquivo de mapeamento não encontrado!\n";
    exit(1);
}

$dados = json_decode(file_get_contents($arquivo_mapeamento), true);
$mapeamento_por_produto = $dados['mapeamento_por_produto'] ?? [];
$keys_livro = $mapeamento_por_produto['impressao-de-livro'] ?? [];

if (empty($keys_livro)) {
    echo "❌ Keys de impressao-de-livro não encontradas!\n";
    exit(1);
}

echo "✅ Carregadas " . count($keys_livro) . " Keys de impressao-de-livro\n";
echo str_repeat("=", 80) . "\n\n";

// Testes
$testes = [
    [
        'quantidade' => 50,
        'formato' => 'A4',
        'papel_capa' => 'Cartão Triplex 250gr',
        'cores_capa' => '4 cores Frente',
        'orelha_capa' => 'SEM ORELHA',
        'acabamento_capa' => 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo' => 'Couche brilho 90gr',
        'cores_miolo' => '4 cores frente e verso',
        'miolo_sangrado' => 'NÃO',
        'quantidade_paginas_miolo' => 'Miolo 8 páginas',
        'acabamento_miolo' => 'Dobrado',
        'acabamento_livro' => 'Grampeado - 2 grampos',
        'guardas_livro' => 'SEM GUARDAS',
        'extras' => 'Nenhum',
        'frete' => 'Incluso',
        'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    ],
    [
        'quantidade' => 100,
        'formato' => 'A5',
        'papel_capa' => 'Cartão Triplex 300gr',
        'cores_capa' => '4 cores Frente e Verso',
        'orelha_capa' => 'COM Orelha de 14cm',
        'acabamento_capa' => 'Laminação BRILHO FRENTE (Acima de 240g)',
        'papel_miolo' => 'Offset 90gr',
        'cores_miolo' => '4 cores frente e verso',
        'miolo_sangrado' => 'SIM',
        'quantidade_paginas_miolo' => 'Miolo 16 páginas',
        'acabamento_miolo' => 'Grampeado - 2 grampos',
        'acabamento_livro' => 'Grampeado - 2 grampos',
        'guardas_livro' => 'COM GUARDAS',
        'extras' => 'Nenhum',
        'frete' => 'Incluso',
        'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    ],
    [
        'quantidade' => 200,
        'formato' => '105x148mm (A6)',
        'papel_capa' => 'Cartão Triplex 250gr',
        'cores_capa' => '4 cores Frente',
        'orelha_capa' => 'SEM ORELHA',
        'acabamento_capa' => 'Laminação FOSCA FRENTE (Acima de 240g)',
        'papel_miolo' => 'Couche brilho 90gr',
        'cores_miolo' => '1 cor (P/B) frente e verso',
        'miolo_sangrado' => 'NÃO',
        'quantidade_paginas_miolo' => 'Miolo 32 páginas',
        'acabamento_miolo' => 'Dobrado',
        'acabamento_livro' => 'Grampeado - 2 grampos',
        'guardas_livro' => 'SEM GUARDAS',
        'extras' => 'Nenhum',
        'frete' => 'Incluso',
        'verificacao_arquivo' => 'Sem Aprovação - Cliente Envia PDF Pronto Para Impressão - (Gratis)',
        'prazo_entrega' => 'Padrão: 10 dias úteis de Produção + tempo de FRETE*'
    ]
];

echo "TESTANDO PREÇOS DE IMPRESSÃO-DE-LIVRO\n";
echo str_repeat("=", 80) . "\n";
echo "Total de testes: " . count($testes) . "\n\n";

$resultados = [];

foreach ($testes as $idx => $opcoes) {
    $num_teste = $idx + 1;
    echo str_repeat("=", 80) . "\n";
    echo "TESTE {$num_teste}/" . count($testes) . "\n";
    echo str_repeat("=", 80) . "\n";
    echo "Quantidade: {$opcoes['quantidade']}\n";
    echo "Formato: {$opcoes['formato']}\n";
    echo "Páginas: {$opcoes['quantidade_paginas_miolo']}\n\n";
    
    // Mapear opções para Keys
    $options = [];
    foreach ($opcoes as $campo => $valor) {
        if ($campo === 'quantidade') {
            continue;
        }
        
        $valor_str = trim((string) $valor);
        
        // Procurar Key exata
        if (isset($keys_livro[$valor_str])) {
            $options[] = [
                'Key' => $keys_livro[$valor_str],
                'Value' => $valor_str
            ];
            echo "   ✅ {$campo}: '{$valor_str}'\n";
        } else {
            // Match parcial (mais flexível)
            $encontrado = false;
            $melhor_match = null;
            $melhor_score = 0;
            $melhor_texto = null;
            
            foreach ($keys_livro as $key_texto => $key_value) {
                $key_texto_trim = trim($key_texto);
                $valor_str_trim = trim($valor_str);
                
                // Match exato (case-insensitive)
                if (strcasecmp($key_texto_trim, $valor_str_trim) === 0) {
                    $options[] = [
                        'Key' => $key_value,
                        'Value' => $key_texto_trim
                    ];
                    $encontrado = true;
                    echo "   ✅ {$campo}: '{$valor_str}' → '{$key_texto_trim}' (match exato CI)\n";
                    break;
                }
                
                // Match parcial - calcular score
                if (stripos($key_texto_trim, $valor_str_trim) !== false || stripos($valor_str_trim, $key_texto_trim) !== false) {
                    // Calcular score baseado no tamanho da correspondência
                    $score = min(strlen($valor_str_trim), strlen($key_texto_trim)) / max(strlen($valor_str_trim), strlen($key_texto_trim));
                    if ($score > $melhor_score) {
                        $melhor_score = $score;
                        $melhor_match = ['Key' => $key_value, 'Value' => $key_texto_trim];
                        $melhor_texto = $key_texto_trim;
                    }
                }
            }
            
            if (!$encontrado && $melhor_match && $melhor_score > 0.5) {
                $options[] = $melhor_match;
                $encontrado = true;
                echo "   ✅ {$campo}: '{$valor_str}' → '{$melhor_texto}' (score: " . round($melhor_score * 100) . "%)\n";
            }
            
            if (!$encontrado) {
                echo "   ❌ Key não encontrada para: {$campo} = '{$valor_str}'\n";
                
                // Mostrar sugestões
                $sugestoes = [];
                $valor_lower = strtolower($valor_str);
                $palavras_valor = array_filter(explode(' ', $valor_lower));
                
                foreach ($keys_livro as $key_texto => $key_value) {
                    $key_lower = strtolower($key_texto);
                    $palavras_key = array_filter(explode(' ', $key_lower));
                    $comuns = array_intersect($palavras_valor, $palavras_key);
                    
                    if (count($comuns) > 0 && count($comuns) >= max(1, count($palavras_valor) * 0.3)) {
                        $sugestoes[] = $key_texto;
                    }
                }
                
                if (!empty($sugestoes)) {
                    echo "      💡 Sugestões (primeiras 3):\n";
                    foreach (array_slice($sugestoes, 0, 3) as $sug) {
                        echo "         - {$sug}\n";
                    }
                }
            }
        }
    }
    
    if (empty($options)) {
        echo "   ❌ Nenhuma opção foi mapeada para Keys!\n\n";
        continue;
    }
    
    echo "   📊 Opções mapeadas: " . count($options) . "\n";
    
    // Chamar API (URL correta)
    $url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing";
    
    $payload = [
        'pricingParameters' => [
            'Q1' => (string) $opcoes['quantidade'],
            'Options' => $options
        ]
    ];
    
    echo "\n📡 Chamando API de pricing...\n";
    echo "   URL: {$url}\n";
    echo "   Q1: {$opcoes['quantidade']}\n";
    echo "   Options: " . count($options) . "\n";
    
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
                echo "   ❌ API retornou erro: {$data['ErrorMessage']}\n";
                $preco_api = null;
            } elseif (isset($data['Cost']) && !empty($data['Cost'])) {
                $preco_api = (float) str_replace(',', '.', $data['Cost']);
                echo "   ✅ Preço obtido via API: R$ " . number_format($preco_api, 2, ',', '.') . "\n";
            } else {
                echo "   ❌ API não retornou campo Cost\n";
                $preco_api = null;
            }
        } else {
            echo "   ❌ Erro HTTP: " . $response->status() . "\n";
            $preco_api = null;
        }
    } catch (\Exception $e) {
        echo "   ❌ Exceção: " . $e->getMessage() . "\n";
        $preco_api = null;
    }
    
    $resultados[] = [
        'teste' => $num_teste,
        'opcoes' => $opcoes,
        'preco_api' => $preco_api ?? null,
        'options_mapeadas' => count($options)
    ];
    
    echo "\n";
    sleep(2);  // Aguardar entre testes
}

// Resumo
echo str_repeat("=", 80) . "\n";
echo "RESUMO FINAL\n";
echo str_repeat("=", 80) . "\n";

foreach ($resultados as $res) {
    echo "Teste {$res['teste']}: ";
    if ($res['preco_api']) {
        echo "✅ R$ " . number_format($res['preco_api'], 2, ',', '.') . " (opções mapeadas: {$res['options_mapeadas']})\n";
    } else {
        echo "❌ Erro ao obter preço\n";
    }
}

echo "\n";

