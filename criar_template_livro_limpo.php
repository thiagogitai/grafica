<?php
/**
 * Cria template limpo de impressao-de-livro do zero,
 * validando que todas as opções estão no mapeamento
 */

$mapeamento = json_decode(file_get_contents('mapeamento_keys_todos_produtos.json'), true);
$keys_livro = $mapeamento['mapeamento_por_produto']['impressao-de-livro'] ?? [];

if (empty($keys_livro)) {
    die("❌ Erro: Mapeamento de impressao-de-livro não encontrado!\n");
}

echo "📚 Criando template limpo para impressao-de-livro\n";
echo str_repeat("=", 70) . "\n\n";

// Template base limpo
$template = [
    'title_override' => 'Impressão de Livro Personalizado',
    'base_price' => null,
    'redirect_to_upload' => true,
    'options' => []
];

// Definir ordem dos campos (baseado na ordem real do site)
$ordem_campos = [
    'quantity',
    'formato_miolo_paginas',
    'papel_capa',
    'cores_capa',
    'orelha_capa',
    'acabamento_capa',
    'papel_miolo',
    'cores_miolo',
    'miolo_sangrado',
    'quantidade_paginas_miolo',
    'acabamento_miolo',
    'acabamento_livro',
    'guardas_livro',
    'extras',
    'frete',
    'verificacao_arquivo',
    'prazo_entrega',
];

// Mapeamento de labels
$labels = [
    'quantity' => '1- Quantidade',
    'formato_miolo_paginas' => '2- Formato do Miolo (Páginas)',
    'papel_capa' => '3- Papel CAPA',
    'cores_capa' => '4- Cores CAPA',
    'orelha_capa' => '5- Orelha CAPA',
    'acabamento_capa' => '6- Acabamento CAPA',
    'papel_miolo' => '7- Papel MIOLO',
    'cores_miolo' => '8- Cores MIOLO',
    'miolo_sangrado' => '9- MIOLO Sangrado?',
    'quantidade_paginas_miolo' => '10- Quantidade Paginas MIOLO',
    'acabamento_miolo' => '11- Acabamento MIOLO',
    'acabamento_livro' => '12- Acabamento LIVRO',
    'guardas_livro' => '13- Guardas LIVRO',
    'extras' => '14- Extras',
    'frete' => '15- Frete',
    'verificacao_arquivo' => '16- Verificação Arquivo',
    'prazo_entrega' => '17- Prazo Entrega',
];

// Agrupar opções por campo
$opcoes_por_campo = [];
foreach ($keys_livro as $texto => $key) {
    // Determinar campo baseado no texto
    $campo = null;
    
    // Formato
    if (preg_match('/^\d+x\d+mm|^\d+mmx\d+mm|A\d+/i', $texto)) {
        $campo = 'formato_miolo_paginas';
    }
    // Papel capa
    elseif (preg_match('/Cartão|Couche|Offset|SEM CAPA|PVC/i', $texto) && 
            (stripos($texto, 'Capa') !== false || stripos($texto, '250gr') !== false || stripos($texto, '300gr') !== false || stripos($texto, '170gr') !== false || stripos($texto, '150gr') !== false)) {
        $campo = 'papel_capa';
    }
    // Papel miolo
    elseif (preg_match('/Couche|Offset|Pólen|Impressão Offset/i', $texto) && 
            (stripos($texto, '75gr') !== false || stripos($texto, '70gr') !== false || stripos($texto, '90gr') !== false || stripos($texto, '115gr') !== false || stripos($texto, '63gr') !== false || stripos($texto, '56gr') !== false || stripos($texto, 'Digital') !== false || stripos($texto, 'Offset') !== false)) {
        $campo = 'papel_miolo';
    }
    // Cores capa (não pode ter "frente e verso" junto, isso é miolo)
    elseif (preg_match('/cores?|Cor|Pantone|Preto/i', $texto) && 
            !preg_match('/frente e verso/i', $texto) &&
            (stripos($texto, 'Frente') !== false || stripos($texto, 'Verso') !== false || stripos($texto, 'FxV') !== false)) {
        $campo = 'cores_capa';
    }
    // Cores miolo (deve vir antes de cores_capa para evitar conflito)
    elseif (preg_match('/frente e verso/i', $texto) && 
            (stripos($texto, 'cores') !== false || stripos($texto, 'cor') !== false || stripos($texto, 'PRETO') !== false)) {
        $campo = 'cores_miolo';
    }
    // Orelha
    elseif (preg_match('/Orelha|ORELHA/i', $texto)) {
        $campo = 'orelha_capa';
    }
    // Acabamento capa
    elseif (preg_match('/Laminação|Verniz|Sem Acabamento/i', $texto)) {
        $campo = 'acabamento_capa';
    }
    // Acabamento miolo
    elseif (preg_match('/Dobrado/i', $texto)) {
        $campo = 'acabamento_miolo';
    }
    // Acabamento livro
    elseif (preg_match('/Colado|Costurado|Espiral|Grampeado|Capa Dura/i', $texto)) {
        $campo = 'acabamento_livro';
    }
    // Guardas
    elseif (preg_match('/GUARDAS|guardas|Couche 170g|offset 180g|Vergê/i', $texto)) {
        $campo = 'guardas_livro';
    }
    // Miolo sangrado
    elseif (preg_match('/^(SIM|NÃO|NÃO)$/i', trim($texto))) {
        $campo = 'miolo_sangrado';
    }
    // Quantidade páginas
    elseif (preg_match('/^Miolo \d+ páginas/i', $texto)) {
        $campo = 'quantidade_paginas_miolo';
    }
    // Extras
    elseif (preg_match('/Shrink|Nenhum/i', $texto)) {
        $campo = 'extras';
    }
    // Frete
    elseif (preg_match('/Incluso|Cliente Retira/i', $texto)) {
        $campo = 'frete';
    }
    // Verificação arquivo
    elseif (preg_match('/Aprovação|Prova|PDF|Digital|Xerox|Plotter/i', $texto)) {
        $campo = 'verificacao_arquivo';
    }
    // Prazo entrega
    elseif (preg_match('/dias úteis|FRETE/i', $texto)) {
        $campo = 'prazo_entrega';
    }
    
    if ($campo) {
        if (!isset($opcoes_por_campo[$campo])) {
            $opcoes_por_campo[$campo] = [];
        }
        $opcoes_por_campo[$campo][] = [
            'value' => $texto,
            'label' => $texto
        ];
    }
}

// Adicionar campo quantity
$template['options'][] = [
    'name' => 'quantity',
    'label' => $labels['quantity'],
    'type' => 'number',
    'default' => 50,
    'min' => 50,
    'step' => 1
];

// Adicionar campos na ordem correta
foreach ($ordem_campos as $campo) {
    if ($campo === 'quantity') continue;
    
    if (isset($opcoes_por_campo[$campo]) && !empty($opcoes_por_campo[$campo])) {
        // Ordenar opções
        usort($opcoes_por_campo[$campo], function($a, $b) use ($campo) {
            // Ordenação especial para quantidade de páginas
            if ($campo === 'quantidade_paginas_miolo') {
                preg_match('/(\d+)/', $a['value'], $ma);
                preg_match('/(\d+)/', $b['value'], $mb);
                return ($ma[1] ?? 0) <=> ($mb[1] ?? 0);
            }
            return strcmp($a['value'], $b['value']);
        });
        
        $template['options'][] = [
            'name' => $campo,
            'label' => $labels[$campo] ?? $campo,
            'type' => 'select',
            'choices' => $opcoes_por_campo[$campo]
        ];
        
        echo "✅ Campo '{$campo}': " . count($opcoes_por_campo[$campo]) . " opções\n";
    } else {
        echo "⚠️  Campo '{$campo}': Nenhuma opção encontrada\n";
    }
}

// Fazer backup do arquivo atual
$arquivo_atual = 'resources/data/products/impressao-de-livro.json';
if (file_exists($arquivo_atual)) {
    $backup = 'resources/data/products/impressao-de-livro.json.backup.' . date('Y-m-d_His');
    copy($arquivo_atual, $backup);
    echo "\n📦 Backup criado: {$backup}\n";
}

// Salvar novo template
file_put_contents($arquivo_atual, json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo "\n✅ Template criado: {$arquivo_atual}\n";
echo "📊 Total de campos: " . count($template['options']) . "\n";
echo "📊 Total de opções mapeadas: " . count($keys_livro) . "\n";

