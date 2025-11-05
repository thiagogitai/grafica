<?php
$keys = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true)['mapeamento_por_produto']['impressao-de-livro'];

echo "🔍 Procurando Keys similares a '105x148mm (A6)':\n\n";

foreach ($keys as $k => $v) {
    if (stripos($k, '105x148') !== false || stripos($k, 'A6') !== false || stripos($k, '105') !== false) {
        echo "   ✅ ENCONTRADA: {$k}\n";
    }
}

echo "\n🔍 Todas as Keys que começam com '105':\n";
foreach ($keys as $k => $v) {
    if (stripos(trim($k), '105') === 0) {
        echo "   - {$k}\n";
    }
}

