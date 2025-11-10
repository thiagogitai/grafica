<?php

/**
 * Testa se o mapeamento está sendo carregado corretamente
 */

$arquivo = 'mapeamento_keys_todos_produtos.json';
$mapeamento = json_decode(file_get_contents($arquivo), true);

$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "Total de keys no mapeamento: " . count($keysMap) . "\n\n";

// Testar valores específicos
$valores = [
    '210x297mm (A4)',
    '210x297mm (A4) ',
    'Couche Fosco 210gr',
    'Couche Fosco 210gr ',
];

foreach ($valores as $valor) {
    $tem = isset($keysMap[$valor]);
    $key = $tem ? $keysMap[$valor] : 'NÃO ENCONTRADO';
    $temEspaco = substr($valor, -1) === ' ';
    echo "Valor: '{$valor}' (length: " . strlen($valor) . ", tem espaço: " . ($temEspaco ? 'SIM' : 'NÃO') . ")\n";
    echo "  Existe no mapeamento: " . ($tem ? 'SIM' : 'NÃO') . "\n";
    if ($tem) {
        echo "  Key: {$key}\n";
    }
    echo "\n";
}

// Testar lógica de conversão
echo "═══════════════════════════════════════════════════════════\n";
echo "TESTE DE CONVERSÃO\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$testeValores = [
    '210x297mm (A4)',
    'Couche Fosco 210gr',
];

foreach ($testeValores as $valorOriginal) {
    $valorTrimmed = trim($valorOriginal);
    $valorComEspaco = $valorTrimmed . ' ';
    
    echo "Valor original: '{$valorOriginal}'\n";
    echo "Valor trimmed: '{$valorTrimmed}'\n";
    echo "Valor com espaço: '{$valorComEspaco}'\n";
    
    $temSemEspaco = isset($keysMap[$valorTrimmed]);
    $temComEspaco = isset($keysMap[$valorComEspaco]);
    
    echo "  Existe sem espaço: " . ($temSemEspaco ? 'SIM' : 'NÃO') . "\n";
    echo "  Existe com espaço: " . ($temComEspaco ? 'SIM' : 'NÃO') . "\n";
    
    if ($temComEspaco) {
        echo "  ✅ DEVE usar versão COM espaço: '{$valorComEspaco}'\n";
        echo "  Key: {$keysMap[$valorComEspaco]}\n";
    } elseif ($temSemEspaco) {
        echo "  ⚠️ Usando versão SEM espaço: '{$valorTrimmed}'\n";
        echo "  Key: {$keysMap[$valorTrimmed]}\n";
    } else {
        echo "  ❌ NÃO ENCONTRADO\n";
    }
    echo "\n";
}

