<?php
/**
 * Script para verificar quais Keys existem no mapeamento de livro
 */

$arquivo_mapeamento = __DIR__ . '/mapeamento_keys_todos_produtos.json';
if (!file_exists($arquivo_mapeamento)) {
    echo "❌ Arquivo não encontrado!\n";
    exit(1);
}

$dados = json_decode(file_get_contents($arquivo_mapeamento), true);
$keys_livro = $dados['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "Total de Keys de livro: " . count($keys_livro) . "\n\n";

// Agrupar por tipo de campo (baseado no texto)
$por_tipo = [
    'formato' => [],
    'papel_capa' => [],
    'cores_capa' => [],
    'orelha_capa' => [],
    'acabamento_capa' => [],
    'papel_miolo' => [],
    'cores_miolo' => [],
    'miolo_sangrado' => [],
    'quantidade_paginas' => [],
    'acabamento_miolo' => [],
    'acabamento_livro' => [],
    'guardas' => [],
    'extras' => [],
    'frete' => [],
    'verificacao' => [],
    'prazo' => []
];

foreach ($keys_livro as $texto => $key) {
    $texto_lower = strtolower($texto);
    
    if (stripos($texto, 'formato') !== false || stripos($texto, 'A4') !== false || stripos($texto, 'A5') !== false || stripos($texto, 'A6') !== false) {
        $por_tipo['formato'][] = $texto;
    } elseif (stripos($texto, 'papel') !== false && stripos($texto, 'capa') !== false) {
        $por_tipo['papel_capa'][] = $texto;
    } elseif (stripos($texto, 'cor') !== false && stripos($texto, 'capa') !== false) {
        $por_tipo['cores_capa'][] = $texto;
    } elseif (stripos($texto, 'orelha') !== false) {
        $por_tipo['orelha_capa'][] = $texto;
    } elseif (stripos($texto, 'acabamento') !== false && stripos($texto, 'capa') !== false) {
        $por_tipo['acabamento_capa'][] = $texto;
    } elseif (stripos($texto, 'papel') !== false && stripos($texto, 'miolo') !== false) {
        $por_tipo['papel_miolo'][] = $texto;
    } elseif (stripos($texto, 'cor') !== false && stripos($texto, 'miolo') !== false) {
        $por_tipo['cores_miolo'][] = $texto;
    } elseif (stripos($texto, 'sangrado') !== false) {
        $por_tipo['miolo_sangrado'][] = $texto;
    } elseif (stripos($texto, 'página') !== false || stripos($texto, 'Miolo') !== false) {
        $por_tipo['quantidade_paginas'][] = $texto;
    } elseif (stripos($texto, 'acabamento') !== false && stripos($texto, 'miolo') !== false) {
        $por_tipo['acabamento_miolo'][] = $texto;
    } elseif (stripos($texto, 'acabamento') !== false && stripos($texto, 'livro') !== false) {
        $por_tipo['acabamento_livro'][] = $texto;
    } elseif (stripos($texto, 'guardas') !== false) {
        $por_tipo['guardas'][] = $texto;
    } elseif (stripos($texto, 'extra') !== false) {
        $por_tipo['extras'][] = $texto;
    } elseif (stripos($texto, 'frete') !== false) {
        $por_tipo['frete'][] = $texto;
    } elseif (stripos($texto, 'verificação') !== false || stripos($texto, 'arquivo') !== false) {
        $por_tipo['verificacao'][] = $texto;
    } elseif (stripos($texto, 'prazo') !== false) {
        $por_tipo['prazo'][] = $texto;
    }
}

echo "KEYS AGRUPADAS POR TIPO:\n";
echo str_repeat("=", 80) . "\n";

foreach ($por_tipo as $tipo => $keys) {
    if (!empty($keys)) {
        echo "\n{$tipo} (" . count($keys) . "):\n";
        foreach (array_slice($keys, 0, 10) as $key) {
            echo "  - {$key}\n";
        }
        if (count($keys) > 10) {
            echo "  ... e mais " . (count($keys) - 10) . "\n";
        }
    }
}

echo "\n\nTODAS AS KEYS (primeiras 30):\n";
echo str_repeat("=", 80) . "\n";
$cont = 0;
foreach ($keys_livro as $texto => $key) {
    echo sprintf("%-60s => %s\n", substr($texto, 0, 60), substr($key, 0, 20) . "...");
    $cont++;
    if ($cont >= 30) break;
}

