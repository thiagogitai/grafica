<?php

/**
 * Script para verificar 100% de concordância entre o template e o mapeamento
 * Identifica todas as discrepâncias de texto, espaços, case, etc.
 */

$templatePath = 'resources/data/products/impressao-de-livro.json';
$mapeamentoPath = 'mapeamento_keys_todos_produtos.json';

echo "🔍 Verificando concordância entre template e mapeamento...\n\n";

// Carregar template
if (!file_exists($templatePath)) {
    die("❌ Template não encontrado: {$templatePath}\n");
}

$template = json_decode(file_get_contents($templatePath), true);
if (!$template) {
    die("❌ Erro ao decodificar template JSON\n");
}

// Carregar mapeamento
if (!file_exists($mapeamentoPath)) {
    die("❌ Mapeamento não encontrado: {$mapeamentoPath}\n");
}

$mapeamentoFull = json_decode(file_get_contents($mapeamentoPath), true);
if (!$mapeamentoFull) {
    die("❌ Erro ao decodificar mapeamento JSON\n");
}

// Pegar mapeamento de impressao-de-livro
$keysMap = $mapeamentoFull['mapeamento_por_produto']['impressao-de-livro'] ?? [];
if (empty($keysMap)) {
    die("❌ Mapeamento de impressao-de-livro não encontrado\n");
}

echo "✅ Template carregado: " . count($template['options'] ?? []) . " campos\n";
echo "✅ Mapeamento carregado: " . count($keysMap) . " keys\n\n";

// Mapear campos do template para os nomes usados no controller
$campoMapping = [
    'formato_miolo_paginas' => 'formato_miolo_paginas',
    'papel_capa' => 'papel_capa',
    'cores_capa' => 'cores_capa',
    'orelha_capa' => 'orelha_capa',
    'acabamento_capa' => 'acabamento_capa',
    'papel_miolo' => 'papel_miolo',
    'cores_miolo' => 'cores_miolo',
    'miolo_sangrado' => 'miolo_sangrado',
    'quantidade_paginas_miolo' => 'quantidade_paginas_miolo',
    'acabamento_miolo' => 'acabamento_miolo',
    'acabamento_livro' => 'acabamento_livro',
    'guardas_livro' => 'guardas_livro',
    'extras' => 'extras',
    'frete' => 'frete',
    'verificacao_arquivo' => 'verificacao_arquivo',
    'prazo_entrega' => 'prazo_entrega',
];

$erros = [];
$avisos = [];
$ok = [];

// Processar cada campo do template
foreach ($template['options'] ?? [] as $opt) {
    $campoName = $opt['name'] ?? null;
    
    // Pular quantity
    if ($campoName === 'quantity') {
        continue;
    }
    
    if (!isset($campoMapping[$campoName])) {
        $avisos[] = "⚠️  Campo '{$campoName}' não está no mapeamento de campos";
        continue;
    }
    
    $choices = $opt['choices'] ?? [];
    
    foreach ($choices as $choice) {
        $valorTemplate = $choice['value'] ?? '';
        $labelTemplate = $choice['label'] ?? '';
        
        if (empty($valorTemplate)) {
            continue;
        }
        
        // Verificar se existe no mapeamento
        $encontradoExato = isset($keysMap[$valorTemplate]);
        $valorTrimmed = trim($valorTemplate);
        $encontradoTrimmed = isset($keysMap[$valorTrimmed]);
        
        // Buscar match case-insensitive
        $matchCaseInsensitive = null;
        $matchComEspaco = null;
        
        foreach ($keysMap as $textoMapeamento => $key) {
            $textoTrimmed = trim($textoMapeamento);
            
            // Match exato case-insensitive
            if (strcasecmp($textoTrimmed, $valorTrimmed) === 0) {
                if ($matchCaseInsensitive === null) {
                    $matchCaseInsensitive = $textoMapeamento;
                }
                
                // Priorizar versão com espaço no final
                if (substr($textoMapeamento, -1) === ' ') {
                    $matchComEspaco = $textoMapeamento;
                }
            }
        }
        
        // Classificar resultado
        if ($encontradoExato) {
            $ok[] = [
                'campo' => $campoName,
                'valor' => $valorTemplate,
                'status' => 'OK_EXATO',
                'key' => $keysMap[$valorTemplate]
            ];
        } elseif ($encontradoTrimmed) {
            $avisos[] = [
                'campo' => $campoName,
                'valor_template' => $valorTemplate,
                'valor_mapeamento' => $valorTrimmed,
                'status' => 'AVISO_TRIM',
                'key' => $keysMap[$valorTrimmed],
                'diferenca' => 'Template tem espaços extras que serão removidos'
            ];
        } elseif ($matchCaseInsensitive) {
            $diferenca = '';
            if ($matchCaseInsensitive !== $valorTemplate) {
                $diferenca = "Template: '{$valorTemplate}' vs Mapeamento: '{$matchCaseInsensitive}'";
            }
            
            $erros[] = [
                'campo' => $campoName,
                'valor_template' => $valorTemplate,
                'valor_mapeamento' => $matchCaseInsensitive,
                'status' => 'ERRO_CASE_ESPACO',
                'key' => $keysMap[$matchCaseInsensitive],
                'diferenca' => $diferenca ?: 'Diferença de case ou espaços'
            ];
        } else {
            $erros[] = [
                'campo' => $campoName,
                'valor_template' => $valorTemplate,
                'status' => 'ERRO_NAO_ENCONTRADO',
                'diferenca' => 'Valor não encontrado no mapeamento'
            ];
        }
    }
}

// Exibir resultados
echo "═══════════════════════════════════════════════════════════\n";
echo "📊 RESULTADO DA VERIFICAÇÃO\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ OK (Exato): " . count($ok) . "\n";
echo "⚠️  AVISOS (Trim): " . count($avisos) . "\n";
echo "❌ ERROS: " . count($erros) . "\n\n";

if (!empty($ok)) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "✅ VALORES OK (Exato)\n";
    echo "═══════════════════════════════════════════════════════════\n";
    foreach (array_slice($ok, 0, 10) as $item) {
        echo "  ✓ {$item['campo']}: '{$item['valor']}' → {$item['key']}\n";
    }
    if (count($ok) > 10) {
        echo "  ... e mais " . (count($ok) - 10) . " valores OK\n";
    }
    echo "\n";
}

if (!empty($avisos)) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "⚠️  AVISOS (Serão corrigidos automaticamente)\n";
    echo "═══════════════════════════════════════════════════════════\n";
    foreach ($avisos as $aviso) {
        if (is_array($aviso)) {
            echo "  ⚠️  {$aviso['campo']}: '{$aviso['valor_template']}' → '{$aviso['valor_mapeamento']}' (trim)\n";
            echo "      Key: {$aviso['key']}\n";
        } else {
            echo "  ⚠️  {$aviso}\n";
        }
    }
    echo "\n";
}

if (!empty($erros)) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "❌ ERROS (Precisam correção no template)\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    // Agrupar por tipo de erro
    $errosPorTipo = [];
    foreach ($erros as $erro) {
        $tipo = $erro['status'] ?? 'DESCONHECIDO';
        if (!isset($errosPorTipo[$tipo])) {
            $errosPorTipo[$tipo] = [];
        }
        $errosPorTipo[$tipo][] = $erro;
    }
    
    foreach ($errosPorTipo as $tipo => $listaErros) {
        echo "\n📌 {$tipo}:\n";
        foreach ($listaErros as $erro) {
            echo "  ❌ Campo: {$erro['campo']}\n";
            echo "     Template: '{$erro['valor_template']}'\n";
            if (isset($erro['valor_mapeamento'])) {
                echo "     Mapeamento: '{$erro['valor_mapeamento']}'\n";
                echo "     Key: {$erro['key']}\n";
            }
            echo "     Diferença: {$erro['diferenca']}\n";
            echo "\n";
        }
    }
    
    // Gerar script de correção
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🔧 SUGESTÃO DE CORREÇÕES\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $correcoes = [];
    foreach ($erros as $erro) {
        if (isset($erro['valor_mapeamento'])) {
            $correcoes[] = [
                'campo' => $erro['campo'],
                'de' => $erro['valor_template'],
                'para' => $erro['valor_mapeamento']
            ];
        }
    }
    
    if (!empty($correcoes)) {
        echo "// Correções necessárias no template:\n";
        foreach ($correcoes as $corr) {
            echo "// Campo '{$corr['campo']}': '{$corr['de']}' → '{$corr['para']}'\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "📈 RESUMO FINAL\n";
echo "═══════════════════════════════════════════════════════════\n";
$total = count($ok) + count($avisos) + count($erros);
$percentualOk = $total > 0 ? round((count($ok) / $total) * 100, 2) : 0;
echo "Total verificado: {$total}\n";
echo "OK: {$percentualOk}%\n";
echo "Avisos: " . round((count($avisos) / $total) * 100, 2) . "%\n";
echo "Erros: " . round((count($erros) / $total) * 100, 2) . "%\n";

if (count($erros) > 0) {
    echo "\n❌ AÇÃO NECESSÁRIA: Corrigir os erros no template antes de usar!\n";
    exit(1);
} else {
    echo "\n✅ Template está 100% compatível com o mapeamento!\n";
    exit(0);
}

