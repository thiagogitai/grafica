<?php
/**
 * Verifica se TODAS as opções de impressao-de-livro estão mapeadas
 */

$json_produto = json_decode(file_get_contents('resources/data/products/impressao-de-livro.json'), true);
$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);

$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "📚 VERIFICAÇÃO DE KEYS PARA impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

$todas_opcoes = [];
$opcoes_faltando = [];

// Coletar TODAS as opções de TODOS os selects
foreach ($json_produto['options'] as $option) {
    if ($option['type'] === 'select' && isset($option['choices'])) {
        foreach ($option['choices'] as $choice) {
            $valor = $choice['value'] ?? $choice['label'] ?? '';
            if ($valor) {
                $todas_opcoes[] = $valor;
                
                // Verificar se está mapeada (com e sem espaços)
                $encontrada = false;
                foreach ($keys_livro as $key_texto => $key_id) {
                    // Comparação case-insensitive e ignorando espaços extras
                    if (strcasecmp(trim($key_texto), trim($valor)) === 0) {
                        $encontrada = true;
                        break;
                    }
                }
                
                if (!$encontrada) {
                    $opcoes_faltando[] = [
                        'campo' => $option['name'],
                        'valor' => $valor
                    ];
                }
            }
        }
    }
}

echo "📊 ESTATÍSTICAS:\n";
echo "   Total de opções no JSON: " . count($todas_opcoes) . "\n";
echo "   Total de keys mapeadas: " . count($keys_livro) . "\n";
echo "   Opções faltando: " . count($opcoes_faltando) . "\n\n";

if (count($opcoes_faltando) > 0) {
    echo "❌ OPÇÕES FALTANDO:\n";
    foreach ($opcoes_faltando as $faltando) {
        echo "   - Campo: {$faltando['campo']}\n";
        echo "     Valor: {$faltando['valor']}\n\n";
    }
} else {
    echo "✅ TODAS AS OPÇÕES ESTÃO MAPEADAS!\n";
}

// Listar todas as keys mapeadas
echo "\n📋 KEYS MAPEADAS (" . count($keys_livro) . "):\n";
$i = 1;
foreach ($keys_livro as $texto => $key) {
    echo sprintf("   %3d. %-60s => %s\n", $i++, substr($texto, 0, 60), $key);
}

