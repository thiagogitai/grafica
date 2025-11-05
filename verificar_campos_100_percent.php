<?php
/**
 * Script para verificar quais campos estão 100% completos (todas as opções têm Keys)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Carregar Keys
$arquivo_mapeamento = __DIR__ . '/mapeamento_keys_todos_produtos.json';
if (!file_exists($arquivo_mapeamento)) {
    echo "❌ Arquivo de mapeamento não encontrado!\n";
    exit(1);
}

$dados = json_decode(file_get_contents($arquivo_mapeamento), true);
$keys_livro = $dados['mapeamento_por_produto']['impressao-de-livro'] ?? [];

if (empty($keys_livro)) {
    echo "❌ Keys de impressao-de-livro não encontradas!\n";
    exit(1);
}

echo "✅ Carregadas " . count($keys_livro) . " Keys de impressao-de-livro\n";
echo str_repeat("=", 80) . "\n\n";

// Carregar template
$arquivo_template = __DIR__ . '/resources/data/products/impressao-de-livro.json';
if (!file_exists($arquivo_template)) {
    echo "❌ Template impressao-de-livro.json não encontrado!\n";
    exit(1);
}

$template = json_decode(file_get_contents($arquivo_template), true);

echo "🔍 VERIFICANDO QUAIS CAMPOS ESTÃO 100% COMPLETOS:\n";
echo str_repeat("=", 80) . "\n\n";

$campos_100_percent = [];
$campos_incompletos = [];

foreach ($template['options'] ?? [] as $opcao_config) {
    $campo = $opcao_config['name'] ?? '';
    $tipo = $opcao_config['type'] ?? '';
    $label = $opcao_config['label'] ?? $campo;
    
    if ($campo === 'quantity' || $tipo === 'number') {
        continue;
    }
    
    // Ler choices (opções do select)
    $choices = $opcao_config['choices'] ?? [];
    $opcoes_array = [];
    foreach ($choices as $choice) {
        $value = $choice['value'] ?? $choice['label'] ?? '';
        if ($value) {
            $opcoes_array[] = trim($value);
        }
    }
    
    if (empty($opcoes_array)) {
        continue;
    }
    
    $keys_encontradas = 0;
    $opcoes_sem_key = [];
    
    foreach ($opcoes_array as $opcao) {
        $opcao_str = trim((string) $opcao);
        
        // Verificar se tem Key EXATA
        if (isset($keys_livro[$opcao_str])) {
            $keys_encontradas++;
            continue;
        }
        
        // Verificar match exato (case-insensitive)
        $encontrado = false;
        foreach ($keys_livro as $key_texto => $key_value) {
            $key_texto_trim = trim($key_texto);
            $opcao_str_trim = trim($opcao_str);
            
            if (strcasecmp($key_texto_trim, $opcao_str_trim) === 0) {
                $keys_encontradas++;
                $encontrado = true;
                break;
            }
        }
        
        if (!$encontrado) {
            $opcoes_sem_key[] = $opcao_str;
        }
    }
    
    $total_opcoes = count($opcoes_array);
    $percentual = $total_opcoes > 0 ? ($keys_encontradas / $total_opcoes) * 100 : 0;
    
    if ($percentual == 100.0) {
        $campos_100_percent[] = [
            'campo' => $campo,
            'label' => $label,
            'total' => $total_opcoes,
            'encontradas' => $keys_encontradas
        ];
    } else {
        $campos_incompletos[] = [
            'campo' => $campo,
            'label' => $label,
            'total' => $total_opcoes,
            'encontradas' => $keys_encontradas,
            'percentual' => $percentual,
            'opcoes_sem_key' => $opcoes_sem_key
        ];
    }
}

echo "✅ CAMPOS 100% COMPLETOS (" . count($campos_100_percent) . "):\n\n";
foreach ($campos_100_percent as $info) {
    echo "   ✅ {$info['label']} ({$info['campo']})\n";
    echo "      Total de opções: {$info['total']}\n";
    echo "      Keys encontradas: {$info['encontradas']}/{$info['total']} (100%)\n\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

echo "❌ CAMPOS INCOMPLETOS (" . count($campos_incompletos) . "):\n\n";
foreach ($campos_incompletos as $info) {
    echo "   ⚠️ {$info['label']} ({$info['campo']})\n";
    echo "      Keys encontradas: {$info['encontradas']}/{$info['total']} (" . number_format($info['percentual'], 1) . "%)\n";
    echo "      Opções sem Key: " . count($info['opcoes_sem_key']) . "\n";
    
    if (!empty($info['opcoes_sem_key'])) {
        echo "      Lista de opções sem Key:\n";
        foreach ($info['opcoes_sem_key'] as $opcao) {
            echo "         ❌ {$opcao}\n";
        }
    }
    echo "\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RESUMO FINAL:\n";
echo "   ✅ Campos 100% completos: " . count($campos_100_percent) . "\n";
echo "   ❌ Campos incompletos: " . count($campos_incompletos) . "\n";
echo "   📋 Total de campos verificados: " . (count($campos_100_percent) + count($campos_incompletos)) . "\n";

