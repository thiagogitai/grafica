<?php
/**
 * Corrige o template usando o backup original (nomes corretos)
 * mas mantendo os labels exatos do site
 */

$backup = 'resources/data/products/impressao-de-livro.json.backup.2025-11-06_122100';
$template_capturado = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

if (!file_exists($backup)) {
    die("❌ Backup não encontrado!\n");
}

echo "🔧 Corrigindo template com labels originais do site\n";
echo str_repeat("=", 70) . "\n\n";

$template_original = json_decode(file_get_contents($backup), true);

// Mapear labels capturados por índice
$labels_capturados = [];
foreach ($template_capturado['options'] as $opt) {
    if ($opt['type'] === 'select') {
        $labels_capturados[] = $opt['label'];
    }
}

// Criar novo template usando estrutura original mas com labels do site
$template_novo = [
    'title_override' => $template_original['title_override'],
    'base_price' => $template_original['base_price'],
    'redirect_to_upload' => $template_original['redirect_to_upload'],
    'options' => []
];

$idx_label = 0;

// Processar cada campo na ordem original
foreach ($template_original['options'] as $option) {
    $campo_novo = [
        'name' => $option['name'],
        'type' => $option['type']
    ];
    
    // Usar label capturado se disponível, senão usar original
    if ($option['type'] === 'select' && isset($labels_capturados[$idx_label])) {
        $campo_novo['label'] = $labels_capturados[$idx_label];
        $idx_label++;
    } else {
        $campo_novo['label'] = $option['label'];
    }
    
    if ($option['type'] === 'number') {
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
            
            // Verificar se está no mapeamento
            $encontrada = false;
            $valor_mapeado = null;
            
            // 1. Match exato
            if (isset($keys_livro[$valor])) {
                $encontrada = true;
                $valor_mapeado = $valor;
            }
            // 2. Match case-insensitive
            else {
                foreach ($keys_livro as $texto_mapeado => $key) {
                    if (strcasecmp(trim($texto_mapeado), trim($valor)) === 0) {
                        $encontrada = true;
                        $valor_mapeado = $texto_mapeado; // Usar valor do mapeamento
                        break;
                    }
                }
            }
            
            if ($encontrada) {
                $choices_validos[] = [
                    'value' => $valor_mapeado,
                    'label' => $choice['label'] ?? $valor_mapeado
                ];
            }
        }
        
        if (!empty($choices_validos)) {
            $campo_novo['choices'] = $choices_validos;
            $template_novo['options'][] = $campo_novo;
            echo "✅ Campo '{$option['name']}': {$campo_novo['label']} - " . count($choices_validos) . " opções\n";
        }
    } else {
        $template_novo['options'][] = $campo_novo;
    }
}

// Salvar
$arquivo_final = 'resources/data/products/impressao-de-livro.json';
file_put_contents($arquivo_final, json_encode($template_novo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n✅ Template corrigido e salvo em: {$arquivo_final}\n";
echo "📊 Total de campos: " . count($template_novo['options']) . "\n";

