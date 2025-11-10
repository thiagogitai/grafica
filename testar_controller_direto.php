<?php

/**
 * Testa o controller diretamente para verificar se está usando espaço
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

// Carregar mapeamento
$arquivo = 'mapeamento_keys_todos_produtos.json';
$mapeamento = json_decode(file_get_contents($arquivo), true);
$keysMap = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

echo "🧪 Testando lógica de mapeamento...\n\n";

// Simular opções como vêm do frontend (sem espaço)
$opcoes = [
    'formato_miolo_paginas' => '210x297mm (A4)',
    'papel_capa' => 'Couche Fosco 210gr',
];

$ordemSelects = [
    0 => 'formato_miolo_paginas',
    1 => 'papel_capa',
];

$options = [];

foreach ($ordemSelects as $selectIdx => $campo) {
    if (!isset($opcoes[$campo])) {
        continue;
    }
    
    $valorStr = (string) $opcoes[$campo];
    $valorStrTrimmed = trim($valorStr);
    $valorComEspaco = $valorStrTrimmed . ' ';
    
    $keyFinal = null;
    $valorFinal = null;
    
    // PRIORIDADE 1: Versão com espaço
    if (isset($keysMap[$valorComEspaco])) {
        $keyFinal = $keysMap[$valorComEspaco];
        $valorFinal = $valorComEspaco;
        echo "✅ Campo: {$campo}\n";
        echo "   Valor original: '{$valorStr}'\n";
        echo "   Valor final: '{$valorFinal}' (tem espaço: " . (substr($valorFinal, -1) === ' ' ? 'SIM' : 'NÃO') . ")\n";
        echo "   Key: {$keyFinal}\n\n";
    }
    // PRIORIDADE 2: Valor original
    elseif (isset($keysMap[$valorStr])) {
        $keyFinal = $keysMap[$valorStr];
        $valorFinal = $valorStr;
        echo "⚠️ Campo: {$campo}\n";
        echo "   Usando valor original: '{$valorFinal}'\n";
        echo "   Key: {$keyFinal}\n\n";
    }
    // PRIORIDADE 3: Valor trimmed
    elseif (isset($keysMap[$valorStrTrimmed])) {
        $keyFinal = $keysMap[$valorStrTrimmed];
        $valorFinal = $valorStrTrimmed;
        echo "⚠️ Campo: {$campo}\n";
        echo "   Usando valor trimmed: '{$valorFinal}'\n";
        echo "   Key: {$keyFinal}\n\n";
    } else {
        echo "❌ Campo: {$campo}\n";
        echo "   Valor: '{$valorStr}'\n";
        echo "   NÃO ENCONTRADO NO MAPEAMENTO\n\n";
    }
    
    if ($keyFinal && $valorFinal) {
        $options[] = [
            'Key' => $keyFinal,
            'Value' => $valorFinal
        ];
    }
}

echo "═══════════════════════════════════════════════════════════\n";
echo "RESULTADO FINAL\n";
echo "═══════════════════════════════════════════════════════════\n";
foreach ($options as $opt) {
    $temEspaco = substr($opt['Value'], -1) === ' ';
    echo "Key: {$opt['Key']}\n";
    echo "Value: '{$opt['Value']}' (length: " . strlen($opt['Value']) . ", tem espaço: " . ($temEspaco ? 'SIM ✅' : 'NÃO ❌') . ")\n\n";
}

