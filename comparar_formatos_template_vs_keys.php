<?php
// Carregar template
$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);

// Carregar Keys
$keys = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true)['mapeamento_por_produto']['impressao-de-livro'];

// Encontrar campo formato
$opcoes_formato_template = [];
foreach ($template['options'] as $opcao_config) {
    if ($opcao_config['name'] === 'formato_miolo_paginas') {
        foreach ($opcao_config['choices'] as $choice) {
            $opcoes_formato_template[] = trim($choice['value']);
        }
        break;
    }
}

echo "📋 OPÇÕES DE FORMATO NO TEMPLATE (" . count($opcoes_formato_template) . "):\n\n";

$keys_formatos = [];
foreach ($keys as $k => $v) {
    // Procurar por padrões de formato (tamanhos)
    if (preg_match('/\d+x\d+/', $k) || stripos($k, 'mm') !== false) {
        $keys_formatos[trim($k)] = $v;
    }
}

echo "📋 KEYS DE FORMATO NO MAPEAMENTO (" . count($keys_formatos) . "):\n\n";

// Comparar
echo "🔍 COMPARAÇÃO:\n\n";
echo str_repeat("=", 80) . "\n";

$opcoes_sem_key = [];
$opcoes_com_key = [];

foreach ($opcoes_formato_template as $opcao_template) {
    $encontrado = false;
    
    foreach ($keys_formatos as $key_texto => $key_value) {
        if (trim($key_texto) === trim($opcao_template)) {
            echo "   ✅ TEMPLATE: '{$opcao_template}' → KEY EXATA: '{$key_texto}'\n";
            $opcoes_com_key[] = $opcao_template;
            $encontrado = true;
            break;
        }
    }
    
    if (!$encontrado) {
        echo "   ❌ TEMPLATE: '{$opcao_template}' → SEM KEY EXATA\n";
        $opcoes_sem_key[] = $opcao_template;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RESUMO:\n";
echo "   ✅ Com Key: " . count($opcoes_com_key) . "/" . count($opcoes_formato_template) . "\n";
echo "   ❌ Sem Key: " . count($opcoes_sem_key) . "/" . count($opcoes_formato_template) . "\n";

if (!empty($opcoes_sem_key)) {
    echo "\n❌ OPÇÕES SEM KEY EXATA (precisam ser removidas ou corrigidas):\n";
    foreach ($opcoes_sem_key as $opcao) {
        echo "   - {$opcao}\n";
    }
}

