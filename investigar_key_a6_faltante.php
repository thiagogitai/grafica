<?php
/**
 * Script para investigar por que a Key "105x148mm (A6)" não foi capturada
 */

$keys = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true)['mapeamento_por_produto']['impressao-de-livro'];

echo "🔍 INVESTIGANDO KEY FALTANTE: '105x148mm (A6)'\n";
echo str_repeat("=", 80) . "\n\n";

$buscar = '105x148mm (A6)';

echo "1️⃣ Verificando se existe variação com espaços ou caracteres especiais:\n\n";
foreach ($keys as $k => $v) {
    $k_trim = trim($k);
    $buscar_trim = trim($buscar);
    
    // Verificar match exato
    if ($k_trim === $buscar_trim) {
        echo "   ✅ ENCONTRADA EXATA: '{$k}'\n";
        echo "      Key Hash: {$v}\n";
        exit(0);
    }
    
    // Verificar variações
    if (stripos($k_trim, '105x148') !== false || stripos($k_trim, '105') !== false && stripos($k_trim, '148') !== false) {
        echo "   📋 Possível variação: '{$k}'\n";
    }
}

echo "\n2️⃣ Verificando todas as Keys que contêm '105' ou '148':\n\n";
$keys_105_148 = [];
foreach ($keys as $k => $v) {
    if (stripos($k, '105') !== false || stripos($k, '148') !== false) {
        $keys_105_148[] = $k;
    }
}

if (!empty($keys_105_148)) {
    echo "   Encontradas " . count($keys_105_148) . " Keys relacionadas:\n";
    foreach ($keys_105_148 as $k) {
        echo "      - {$k}\n";
    }
} else {
    echo "   ❌ Nenhuma Key encontrada com '105' ou '148'\n";
}

echo "\n3️⃣ Verificando Keys de formato A6:\n\n";
$keys_a6 = [];
foreach ($keys as $k => $v) {
    if (stripos($k, 'A6') !== false) {
        $keys_a6[] = $k;
    }
}

if (!empty($keys_a6)) {
    echo "   Encontradas " . count($keys_a6) . " Keys com 'A6':\n";
    foreach ($keys_a6 as $k) {
        echo "      - {$k}\n";
    }
} else {
    echo "   ❌ Nenhuma Key encontrada com 'A6'\n";
}

echo "\n4️⃣ Verificando se o formato está no site matriz:\n\n";
echo "   💡 Provavelmente esta opção não existe no site matriz ou tem nome diferente.\n";
echo "   💡 Sugestões:\n";
echo "      - Verificar manualmente no site se existe '105x148mm (A6)'\n";
echo "      - Pode ser que o site use apenas '105x148mm' sem o '(A6)'\n";
echo "      - Ou pode ser que essa opção não esteja disponível para livro\n\n";

echo "5️⃣ Verificando Keys que começam com '105x148':\n\n";
foreach ($keys as $k => $v) {
    if (stripos(trim($k), '105x148') === 0) {
        echo "   ✅ Key encontrada: '{$k}'\n";
        echo "      Key Hash: {$v}\n";
        echo "\n   💡 Sugestão: O template pode estar usando '105x148mm (A6)' mas o site usa '{$k}'\n";
        echo "      Corrija o template para usar: '{$k}'\n";
    }
}

