<?php
/**
 * Valida o template capturado e garante que todos os valores estão no mapeamento
 */

$template = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "🔍 VALIDANDO TEMPLATE CAPTURADO\n";
echo str_repeat("=", 70) . "\n\n";

$total_opcoes = 0;
$opcoes_validas = 0;
$opcoes_sem_mapeamento = [];

foreach ($template['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        echo "📋 Campo: {$option['name']} - {$option['label']}\n";
        
        foreach ($option['choices'] as $choice) {
            $valor = $choice['value'] ?? '';
            $total_opcoes++;
            
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
                $opcoes_validas++;
                // Atualizar valor para corresponder ao mapeamento
                if ($valor_mapeado !== $valor) {
                    $choice['value'] = $valor_mapeado;
                }
            } else {
                $opcoes_sem_mapeamento[] = [
                    'campo' => $option['name'],
                    'valor' => $valor
                ];
            }
        }
        
        echo "   Total de opções: " . count($option['choices']) . "\n";
    }
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "📊 RESULTADO:\n";
echo "   Total de opções no template: {$total_opcoes}\n";
echo "   Opções com mapeamento: {$opcoes_validas}\n";
echo "   Opções sem mapeamento: " . count($opcoes_sem_mapeamento) . "\n";

if (!empty($opcoes_sem_mapeamento)) {
    echo "\n⚠️  Opções sem mapeamento (primeiras 10):\n";
    foreach (array_slice($opcoes_sem_mapeamento, 0, 10) as $item) {
        echo "   - {$item['campo']}: {$item['valor']}\n";
    }
}

// Salvar template corrigido
file_put_contents('resources/data/products/impressao-de-livro.json', json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n✅ Template validado e salvo!\n";

