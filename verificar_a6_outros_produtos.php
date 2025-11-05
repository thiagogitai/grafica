<?php
/**
 * Verificar se o formato A6 existe em outros produtos
 */

$arquivo_mapeamento = __DIR__ . '/mapeamento_keys_todos_produtos.json';
$dados = json_decode(file_get_contents($arquivo_mapeamento), true);

$produtos = [
    'impressao-de-revista',
    'impressao-de-tabloide',
    'impressao-de-livro',
    'impressao-de-panfleto',
    'impressao-de-apostila',
];

echo "🔍 VERIFICANDO SE '105x148mm (A6)' EXISTE EM OUTROS PRODUTOS:\n";
echo str_repeat("=", 80) . "\n\n";

$encontrado_em = [];

foreach ($produtos as $produto) {
    $keys = $dados['mapeamento_por_produto'][$produto] ?? [];
    
    if (empty($keys)) {
        continue;
    }
    
    foreach ($keys as $k => $v) {
        if (stripos($k, '105x148') !== false && stripos($k, 'A6') !== false) {
            $encontrado_em[] = [
                'produto' => $produto,
                'texto' => $k,
                'key' => $v
            ];
        }
    }
}

if (!empty($encontrado_em)) {
    echo "✅ FORMATO ENCONTRADO!\n\n";
    foreach ($encontrado_em as $item) {
        echo "   Produto: {$item['produto']}\n";
        echo "   Texto: '{$item['texto']}'\n";
        echo "   Key: {$item['key']}\n\n";
    }
} else {
    echo "❌ FORMATO '105x148mm (A6)' NÃO ENCONTRADO EM NENHUM PRODUTO!\n\n";
    echo "💡 CONCLUSÃO:\n";
    echo "   O formato '105x148mm (A6)' não existe no site matriz.\n";
    echo "   Portanto, não há Key para ele e não pode ser usado.\n";
    echo "\n   A opção deve ser REMOVIDA do template 'impressao-de-livro.json'.\n";
}

