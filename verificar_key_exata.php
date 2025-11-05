<?php
$keys = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true)['mapeamento_por_produto']['impressao-de-livro'];

echo "🔍 Verificando se existe Key EXATA para '105x148mm (A6)':\n\n";

$buscar = '105x148mm (A6)';
$encontrado_exato = false;

foreach ($keys as $k => $v) {
    if (trim($k) === trim($buscar)) {
        echo "   ✅ KEY EXATA ENCONTRADA: '{$k}'\n";
        echo "      Key Hash: {$v}\n";
        $encontrado_exato = true;
        break;
    }
}

if (!$encontrado_exato) {
    echo "   ❌ KEY EXATA NÃO ENCONTRADA\n\n";
    echo "🔍 Procurando todas as Keys relacionadas a formato (primeiras 30):\n\n";
    
    $count = 0;
    foreach ($keys as $k => $v) {
        // Procurar por padrões de formato (tamanhos)
        if (preg_match('/\d+x\d+/', $k) || stripos($k, 'mm') !== false || stripos($k, 'A4') !== false || stripos($k, 'A5') !== false || stripos($k, 'A6') !== false) {
            echo "   - {$k}\n";
            $count++;
            if ($count >= 30) {
                echo "   ... (mostrando apenas primeiras 30)\n";
                break;
            }
        }
    }
}

