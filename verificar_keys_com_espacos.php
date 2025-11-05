<?php
$keys = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true)['mapeamento_por_produto']['impressao-de-livro'];

echo "Keys que contêm 'Couche Brilho 150gr':\n";
foreach($keys as $k => $v) {
    if (stripos($k, 'Couche Brilho 150gr') !== false) {
        echo "  '" . $k . "' => " . $v . "\n";
        echo "  Tamanho: " . strlen($k) . " caracteres\n";
        echo "  Último caractere (ASCII): " . ord(substr($k, -1)) . "\n\n";
    }
}

