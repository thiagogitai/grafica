<?php
/**
 * Corrige valores do template para corresponderem exatamente ao mapeamento
 */

$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "🔧 Corrigindo valores do template para corresponderem ao mapeamento\n";
echo str_repeat("=", 70) . "\n\n";

$corrigidos = 0;
$nao_encontrados = [];

foreach ($template['options'] as &$option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        foreach ($option['choices'] as &$choice) {
            $valor_original = $choice['value'] ?? '';
            if (!$valor_original) continue;
            
            // Procurar no mapeamento (exato ou case-insensitive)
            $valor_mapeado = null;
            
            // 1. Tentar match exato
            if (isset($keys_livro[$valor_original])) {
                $valor_mapeado = $valor_original;
            } else {
                // 2. Tentar match case-insensitive
                foreach ($keys_livro as $texto_mapeado => $key) {
                    if (strcasecmp(trim($texto_mapeado), trim($valor_original)) === 0) {
                        $valor_mapeado = $texto_mapeado; // Usar valor do mapeamento (pode ter espaços)
                        break;
                    }
                }
            }
            
            if ($valor_mapeado && $valor_mapeado !== $valor_original) {
                echo "✅ Corrigido: '{$valor_original}' => '{$valor_mapeado}'\n";
                $choice['value'] = $valor_mapeado;
                $corrigidos++;
            } elseif (!$valor_mapeado) {
                $nao_encontrados[] = [
                    'campo' => $option['name'],
                    'valor' => $valor_original
                ];
            }
        }
    }
}

// Salvar template corrigido
file_put_contents('resources/data/products/impressao-de-livro.json', json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTADO:\n";
echo "   Valores corrigidos: {$corrigidos}\n";
echo "   Valores não encontrados no mapeamento: " . count($nao_encontrados) . "\n";

if (!empty($nao_encontrados)) {
    echo "\n⚠️  Valores não encontrados (primeiros 10):\n";
    foreach (array_slice($nao_encontrados, 0, 10) as $item) {
        echo "   - {$item['campo']}: {$item['valor']}\n";
    }
}

echo "\n✅ Template corrigido e salvo!\n";

