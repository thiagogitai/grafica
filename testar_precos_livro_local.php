<?php
/**
 * Script para testar preços de impressao-de-livro LOCALMENTE
 * Compara preços obtidos via API com valores esperados
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

// Carregar Keys mapeadas
$arquivo_mapeamento = __DIR__ . '/mapeamento_keys_todos_produtos.json';
if (!file_exists($arquivo_mapeamento)) {
    echo "❌ Arquivo de mapeamento não encontrado!\n";
    echo "   Execute: scp root@srv1097663:/www/wwwroot/grafica/mapeamento_keys_todos_produtos.json .\n";
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

// Carregar template JSON do produto
$arquivo_template = __DIR__ . '/resources/data/products/impressao-de-livro.json';
if (!file_exists($arquivo_template)) {
    echo "❌ Template impressao-de-livro.json não encontrado!\n";
    exit(1);
}

$template = json_decode(file_get_contents($arquivo_template), true);
echo "✅ Template carregado: " . count($template['options'] ?? []) . " campos\n\n";

// Verificar quais opções do template têm Keys
echo "🔍 VERIFICANDO MAPEAMENTO DE KEYS DO TEMPLATE:\n";
echo str_repeat("=", 80) . "\n";

$campos_sem_keys = [];
$campos_com_keys = [];

foreach ($template['options'] ?? [] as $opcao_config) {
    $campo = $opcao_config['name'] ?? '';
    $tipo = $opcao_config['type'] ?? '';
    
    if ($campo === 'quantity' || $tipo === 'number') {
        continue;
    }
    
    // Ler choices (opções do select)
    $choices = $opcao_config['choices'] ?? [];
    $opcoes_array = [];
    foreach ($choices as $choice) {
        $value = $choice['value'] ?? $choice['label'] ?? '';
        if ($value) {
            $opcoes_array[] = $value;
        }
    }
    
    if (empty($opcoes_array)) {
        continue;
    }
    
    $keys_encontradas = 0;
    $opcoes_sem_key = [];
    
    foreach ($opcoes_array as $opcao) {
        $opcao_str = trim((string) $opcao);
        
        // Verificar se tem Key exata
        if (isset($keys_livro[$opcao_str])) {
            $keys_encontradas++;
            continue;
        }
        
        // Verificar match parcial
        $encontrado = false;
        foreach ($keys_livro as $key_texto => $key_value) {
            $key_texto_trim = trim($key_texto);
            $opcao_str_trim = trim($opcao_str);
            
            if (strcasecmp($key_texto_trim, $opcao_str_trim) === 0) {
                $keys_encontradas++;
                $encontrado = true;
                break;
            }
            
            if (stripos($key_texto_trim, $opcao_str_trim) !== false || stripos($opcao_str_trim, $key_texto_trim) !== false) {
                $score = min(strlen($opcao_str_trim), strlen($key_texto_trim)) / max(strlen($opcao_str_trim), strlen($key_texto_trim));
                if ($score > 0.7) {
                    $keys_encontradas++;
                    $encontrado = true;
                    break;
                }
            }
        }
        
        if (!$encontrado) {
            $opcoes_sem_key[] = $opcao_str;
        }
    }
    
    $total_opcoes = count($opcoes_array);
    $percentual = $total_opcoes > 0 ? ($keys_encontradas / $total_opcoes) * 100 : 0;
    
    if ($percentual < 100) {
        $campos_sem_keys[$campo] = [
            'encontradas' => $keys_encontradas,
            'total' => $total_opcoes,
            'percentual' => $percentual,
            'opcoes_sem_key' => $opcoes_sem_key
        ];
    } else {
        $campos_com_keys[$campo] = [
            'encontradas' => $keys_encontradas,
            'total' => $total_opcoes
        ];
    }
}

echo "\n✅ CAMPOS COM TODAS AS KEYS (" . count($campos_com_keys) . "):\n";
foreach ($campos_com_keys as $campo => $info) {
    echo "   ✅ {$campo}: {$info['encontradas']}/{$info['total']} Keys\n";
}

echo "\n❌ CAMPOS COM KEYS FALTANDO (" . count($campos_sem_keys) . "):\n";
foreach ($campos_sem_keys as $campo => $info) {
    echo "\n   ⚠️ {$campo}: {$info['encontradas']}/{$info['total']} Keys (" . number_format($info['percentual'], 1) . "%)\n";
    if (!empty($info['opcoes_sem_key'])) {
        echo "      Opções sem Key (" . count($info['opcoes_sem_key']) . "):\n";
        foreach (array_slice($info['opcoes_sem_key'], 0, 10) as $opcao) {
            echo "         - {$opcao}\n";
            
            // Mostrar sugestões
            $sugestoes = [];
            $opcao_lower = strtolower($opcao);
            $palavras_opcao = array_filter(explode(' ', $opcao_lower));
            
            foreach ($keys_livro as $key_texto => $key_value) {
                $key_lower = strtolower($key_texto);
                $palavras_key = array_filter(explode(' ', $key_lower));
                $comuns = array_intersect($palavras_opcao, $palavras_key);
                
                if (count($comuns) > 0 && count($comuns) >= max(1, count($palavras_opcao) * 0.3)) {
                    $sugestoes[] = $key_texto;
                }
            }
            
            if (!empty($sugestoes)) {
                echo "           💡 Sugestões:\n";
                foreach (array_slice($sugestoes, 0, 3) as $sug) {
                    echo "              - {$sug}\n";
                }
            }
        }
        if (count($info['opcoes_sem_key']) > 10) {
            echo "         ... e mais " . (count($info['opcoes_sem_key']) - 10) . " opções\n";
        }
    }
}

// Testes práticos
echo "\n\n" . str_repeat("=", 80) . "\n";
echo "TESTANDO PREÇOS COM OPÇÕES DO TEMPLATE\n";
echo str_repeat("=", 80) . "\n\n";

// Pegar primeiras opções de cada campo para teste
$opcoes_teste = ['quantidade' => 50];
foreach ($template['options'] ?? [] as $opcao_config) {
    $campo = $opcao_config['name'] ?? '';
    $tipo = $opcao_config['type'] ?? '';
    
    if ($campo === 'quantity' || $tipo === 'number') {
        continue;
    }
    
    // Ler choices (opções do select)
    $choices = $opcao_config['choices'] ?? [];
    if (!empty($choices)) {
        $primeira_choice = $choices[0];
        $value = $primeira_choice['value'] ?? $primeira_choice['label'] ?? '';
        if ($value) {
            $opcoes_teste[$campo] = $value;
        }
    }
}

echo "📋 Opções de teste (primeira opção de cada campo):\n";
foreach ($opcoes_teste as $campo => $valor) {
    echo "   {$campo}: {$valor}\n";
}

echo "\n📡 Mapeando para Keys...\n";

$options = [];
$opcoes_nao_encontradas = [];

foreach ($opcoes_teste as $campo => $valor) {
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
        continue;
    }
    
    // Match parcial
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
        
        // Match parcial
        if (stripos($key_texto_trim, $valor_str_trim) !== false || stripos($valor_str_trim, $key_texto_trim) !== false) {
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
        $opcoes_nao_encontradas[] = [$campo, $valor_str];
    }
}

if (empty($options)) {
    echo "\n❌ Nenhuma opção foi mapeada para Keys!\n";
    exit(1);
}

echo "\n📊 Total de opções mapeadas: " . count($options) . "\n";
echo "📊 Opções não encontradas: " . count($opcoes_nao_encontradas) . "\n";

if (!empty($opcoes_nao_encontradas)) {
    echo "\n⚠️ ATENÇÃO: Algumas opções não foram encontradas!\n";
    echo "   Corrija o template antes de testar a API.\n";
    exit(1);
}

// Chamar API
$url = "https://www.lojagraficaeskenazi.com.br/product/impressao-de-livro/pricing";

$payload = [
    'pricingParameters' => [
        'Q1' => (string) $opcoes_teste['quantidade'],
        'Options' => $options
    ]
];

echo "\n📡 Chamando API de pricing...\n";
echo "   URL: {$url}\n";
echo "   Q1: {$opcoes_teste['quantidade']}\n";
echo "   Options: " . count($options) . "\n";
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
            echo "\n✅ PREÇO OBTIDO: R$ " . number_format($preco, 2, ',', '.') . "\n";
            echo "   FormattedCost: " . ($data['FormattedCost'] ?? 'N/A') . "\n";
        } else {
            echo "\n❌ API não retornou campo Cost\n";
            echo "   Response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "\n❌ Erro HTTP: " . $response->status() . "\n";
        echo "   Body: " . $response->body() . "\n";
    }
} catch (\Exception $e) {
    echo "\n❌ Exceção: " . $e->getMessage() . "\n";
}

