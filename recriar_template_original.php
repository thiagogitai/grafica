<?php
/**
 * Recria template usando o arquivo original como base,
 * garantindo que todas as opções estejam no mapeamento
 */

$backup = 'resources/data/products/impressao-de-livro.json.backup.2025-11-06_122100';
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

if (!file_exists($backup)) {
    die("❌ Backup não encontrado: {$backup}\n");
}

echo "📚 Recriando template a partir do original\n";
echo str_repeat("=", 70) . "\n\n";

$template_original = json_decode(file_get_contents($backup), true);

// Criar novo template mantendo estrutura original
$template_novo = [
    'title_override' => $template_original['title_override'],
    'base_price' => $template_original['base_price'],
    'redirect_to_upload' => $template_original['redirect_to_upload'],
    'options' => []
];

$total_opcoes_original = 0;
$total_opcoes_validas = 0;
$opcoes_removidas = [];

// Processar cada campo na ordem original
foreach ($template_original['options'] as $option) {
    $campo_novo = [
        'name' => $option['name'],
        'label' => $option['label'],
        'type' => $option['type']
    ];
    
    if ($option['type'] === 'number') {
        // Copiar propriedades numéricas
        if (isset($option['default'])) $campo_novo['default'] = $option['default'];
        if (isset($option['min'])) $campo_novo['min'] = $option['min'];
        if (isset($option['max'])) $campo_novo['max'] = $option['max'];
        if (isset($option['step'])) $campo_novo['step'] = $option['step'];
        
        $template_novo['options'][] = $campo_novo;
    } elseif ($option['type'] === 'select' && isset($option['choices'])) {
        $choices_validos = [];
        
        foreach ($option['choices'] as $choice) {
            $valor = $choice['value'] ?? $choice['label'] ?? '';
            if (!$valor) continue;
            
            $total_opcoes_original++;
            
            // Verificar se está no mapeamento
            $encontrada = false;
            $valor_mapeado = null;
            
            foreach ($keys_livro as $key_texto => $key_id) {
                if (strcasecmp(trim($key_texto), trim($valor)) === 0) {
                    $encontrada = true;
                    $valor_mapeado = $key_texto; // Usar valor do mapeamento (pode ter espaços)
                    break;
                }
            }
            
            if ($encontrada) {
                $choices_validos[] = [
                    'value' => $valor_mapeado ?? $valor,
                    'label' => $choice['label'] ?? $valor
                ];
                $total_opcoes_validas++;
            } else {
                $opcoes_removidas[] = [
                    'campo' => $option['name'],
                    'valor' => $valor
                ];
            }
        }
        
        if (!empty($choices_validos)) {
            $campo_novo['choices'] = $choices_validos;
            $template_novo['options'][] = $campo_novo;
            echo "✅ Campo '{$option['name']}': " . count($choices_validos) . " opções válidas\n";
        } else {
            echo "⚠️  Campo '{$option['name']}': Nenhuma opção válida encontrada\n";
        }
    } else {
        // Outros tipos, copiar como está
        $template_novo['options'][] = $campo_novo;
    }
}

// Salvar
$arquivo_final = 'resources/data/products/impressao-de-livro.json';
file_put_contents($arquivo_final, json_encode($template_novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTADO:\n";
echo "   Total de opções no original: {$total_opcoes_original}\n";
echo "   Total de opções válidas (mapeadas): {$total_opcoes_validas}\n";
echo "   Opções removidas (sem mapeamento): " . count($opcoes_removidas) . "\n";
echo "   Total de campos: " . count($template_novo['options']) . "\n";

if (!empty($opcoes_removidas)) {
    echo "\n⚠️  Opções removidas (primeiras 10):\n";
    foreach (array_slice($opcoes_removidas, 0, 10) as $item) {
        echo "   - {$item['campo']}: {$item['valor']}\n";
    }
    if (count($opcoes_removidas) > 10) {
        echo "   ... e mais " . (count($opcoes_removidas) - 10) . " opções\n";
    }
}

echo "\n✅ Template salvo em: {$arquivo_final}\n";

